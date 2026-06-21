<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'attendance_date' => $this->attendance_date?->toDateString(),
            'check_in' => $this->check_in?->toISOString(),
            'check_out' => $this->check_out?->toISOString(),
            'total_minutes' => $this->total_minutes,
            'total_hours' => $this->total_hours,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'ip_address' => $this->ip_address,
            'device_info' => $this->device_info,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
