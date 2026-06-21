<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AttendanceRecord;
use App\Models\SystemSetting;
use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendanceService
{
    public function __construct(private readonly AttendanceRepository $attendanceRepository)
    {
    }

    public function checkIn(int $userId, array $data, Request $request): AttendanceRecord
    {
        if ($this->attendanceRepository->findTodayRecord($userId)) {
            throw ValidationException::withMessages([
                'attendance' => ['You have already checked in for today.'],
            ]);
        }

        $status = $this->calculateCheckInStatus(now());

        return $this->attendanceRepository->create([
            'user_id' => $userId,
            'attendance_date' => today(),
            'check_in' => now(),
            'status' => $status,
            'remarks' => $data['remarks'] ?? null,
            'ip_address' => $request->ip(),
            'device_info' => $request->userAgent(),
        ]);
    }

    public function checkOut(int $userId, Request $request): AttendanceRecord
    {
        $record = $this->attendanceRepository->findTodayRecord($userId);

        if (! $record || ! $record->check_in) {
            throw ValidationException::withMessages([
                'attendance' => ['No check-in record exists for today.'],
            ]);
        }

        if ($record->check_out) {
            throw ValidationException::withMessages([
                'attendance' => ['You have already checked out for today.'],
            ]);
        }

        $checkOut = now();
        $totalMinutes = $record->check_in->diffInMinutes($checkOut);
        $status = $this->calculateCheckOutStatus($record->status, $totalMinutes);

        return $this->attendanceRepository->update($record, [
            'check_out' => $checkOut,
            'total_minutes' => $totalMinutes,
            'status' => $status,
        ]);
    }

    public function adjustAttendance(int $id, array $updates, Request $request): AttendanceRecord
    {
        $record = $this->attendanceRepository->findById($id);

        if (! $record) {
            abort(404, 'Attendance record not found.');
        }

        $oldData = $record->only([
            'check_in',
            'check_out',
            'total_minutes',
            'status',
            'remarks',
        ]);

        $record->fill($updates);

        if ($record->check_in && $record->check_out) {
            $record->total_minutes = $record->check_in->diffInMinutes($record->check_out);
        }

        $record->save();

        AuditLog::record([
            'type' => 'attendance_adjustment',
            'model_id' => $record->id,
            'old_data' => $oldData,
            'new_data' => $record->only([
                'check_in',
                'check_out',
                'total_minutes',
                'status',
                'remarks',
            ]),
            'created_at' => now(),
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'module' => 'attendance',
            'action' => 'attendance_adjustment',
        ]);

        return $record->refresh();
    }

    public function listAttendance(array $filters): mixed
    {
        return $this->attendanceRepository->paginate($filters, $filters['per_page'] ?? 15);
    }

    public function getAttendance(int $id): AttendanceRecord
    {
        $record = $this->attendanceRepository->findById($id);

        if (! $record) {
            abort(404, 'Attendance record not found.');
        }

        return $record;
    }

    private function calculateCheckInStatus(Carbon $now): string
    {
        $startTime = SystemSetting::getValue('office_start_time', '09:00');
        $lateThreshold = (int) SystemSetting::getValue('late_threshold_minutes', '15');

        $startOfWork = Carbon::parse($startTime)->setDate($now->year, $now->month, $now->day);
        $lateCutoff = $startOfWork->copy()->addMinutes($lateThreshold);

        return $now->greaterThan($lateCutoff) ? 'late' : 'present';
    }

    private function calculateCheckOutStatus(string $currentStatus, int $totalMinutes): string
    {
        $startTime = SystemSetting::getValue('office_start_time', '09:00');
        $endTime = SystemSetting::getValue('office_end_time', '18:00');

        $expectedMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));

        if ($totalMinutes < (int) floor($expectedMinutes / 2)) {
            return 'half_day';
        }

        return $currentStatus;
    }
}
