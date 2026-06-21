<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'requested_by',
        'reviewed_by',
        'corrected_check_in',
        'corrected_check_out',
        'corrected_status',
        'reason',
        'status',
        'reviewer_remarks',
        'reviewed_at',
    ];

    protected $casts = [
        'corrected_check_in' => 'datetime',
        'corrected_check_out' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
