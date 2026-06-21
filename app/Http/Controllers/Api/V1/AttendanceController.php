<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AdjustAttendanceRequest;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Requests\Attendance\ListAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService)
    {
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        return $this->success(
            new AttendanceResource($this->attendanceService->checkIn($request->user()->id, $request->validated(), $request)),
            'Checked in successfully.'
        );
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        return $this->success(
            new AttendanceResource($this->attendanceService->checkOut($request->user()->id, $request)),
            'Checked out successfully.'
        );
    }

    public function index(ListAttendanceRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if (! $request->user()->hasAnyRole(['super-admin', 'hr-manager'])) {
            $filters['user_id'] = $request->user()->id;
        }

        return $this->success(
            AttendanceResource::collection($this->attendanceService->listAttendance($filters)),
            'Attendance records retrieved.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->attendanceService->getAttendance($id);

        if (! $this->canViewRecord($record)) {
            abort(403, 'You are not authorized to view this attendance record.');
        }

        return $this->success(new AttendanceResource($record), 'Attendance record retrieved.');
    }

    public function adjust(AdjustAttendanceRequest $request, int $id): JsonResponse
    {
        return $this->success(
            new AttendanceResource($this->attendanceService->adjustAttendance($id, $request->validated(), $request)),
            'Attendance record adjusted successfully.'
        );
    }

    private function canViewRecord($record): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super-admin', 'hr-manager'])) {
            return true;
        }

        return $record->user_id === $user->id;
    }
}
