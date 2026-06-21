<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceRepository
{
    public function findTodayRecord(int $userId): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('user_id', $userId)
            ->whereDate('attendance_date', today())
            ->first();
    }

    public function create(array $data): AttendanceRecord
    {
        return AttendanceRecord::query()->create($data);
    }

    public function update(AttendanceRecord $record, array $data): AttendanceRecord
    {
        $record->fill($data);
        $record->save();

        return $record->refresh();
    }

    public function findById(int $id): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->with('user')
            ->find($id);
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AttendanceRecord::query()
            ->with('user')
            ->orderByDesc('attendance_date');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['department_id'])) {
            $query->whereHas('user', function ($query) use ($filters): void {
                $query->where('department_id', $filters['department_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('attendance_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('attendance_date', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage);
    }
}
