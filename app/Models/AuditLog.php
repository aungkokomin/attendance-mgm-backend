<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'type',
        'model_id',
        'old_data',
        'new_data',
        'created_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Record an audit entry compatible with either audit schema variant.
     *
     * @param array<string,mixed> $data
     */
    public static function record(array $data): void
    {
        // New schema (type, model_id)
        if (Schema::hasColumn('audit_logs', 'type') && Schema::hasColumn('audit_logs', 'model_id')) {
            DB::table('audit_logs')->insert([
                'type' => $data['type'] ?? $data['module'] ?? 'audit',
                'model_id' => $data['model_id'] ?? null,
                'old_data' => isset($data['old_data']) ? json_encode($data['old_data']) : null,
                'new_data' => isset($data['new_data']) ? json_encode($data['new_data']) : null,
                'created_at' => $data['created_at'] ?? now(),
            ]);

            return;
        }

        // Legacy schema (module, action, user_id, ip_address, user_agent)
        DB::table('audit_logs')->insert([
            'user_id' => $data['user_id'] ?? null,
            'module' => $data['module'] ?? ($data['type'] ?? 'audit'),
            'action' => $data['action'] ?? ($data['type'] ?? 'action'),
            'old_data' => isset($data['old_data']) ? json_encode($data['old_data']) : null,
            'new_data' => isset($data['new_data']) ? json_encode($data['new_data']) : null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'created_at' => $data['created_at'] ?? now(),
        ]);
    }
}
