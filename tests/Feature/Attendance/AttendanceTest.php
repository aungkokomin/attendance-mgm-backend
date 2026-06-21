<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\PersonalAccessToken;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_can_check_in(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::createTokenFor($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in', [
                'remarks' => 'Arrived at office',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
        ]);
    }

    public function test_employee_cannot_check_in_twice_on_same_day(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in', [])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in', [])->assertUnprocessable();
    }

    public function test_employee_can_check_out(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-out')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
        ]);
    }

    public function test_employee_cannot_check_out_without_check_in(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-out')
            ->assertUnprocessable();
    }

    public function test_hr_can_adjust_attendance_and_audit_log_is_created(): void
    {
        $user = User::factory()->create();
        $hr = User::factory()->create();

        // ensure HR role exists and assign it if spatie roles are available
        try {
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'hr-manager']);
            }

            $hr->assignRole('hr-manager');
        } catch (\Throwable $e) {
            // ignore if roles package isn't set up in the test environment
        }

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($hr);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/attendance/'.$record->id, [
                'remarks' => 'Adjusted hours',
            ])
            ->assertOk();

        // Support both audit_logs schema variants used in different environments.
        if (\Illuminate\Support\Facades\Schema::hasColumn('audit_logs', 'model_id')) {
            $this->assertDatabaseHas('audit_logs', [
                'model_id' => $record->id,
                'type' => 'attendance_adjustment',
            ]);
        } else {
            $this->assertDatabaseHas('audit_logs', [
                'module' => 'attendance',
                'action' => 'attendance_adjustment',
            ]);
        }
    }

    public function test_late_status_assigned_when_checking_in_after_threshold(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::createTokenFor($user);

        // set office start to 09:00 and threshold to 15 minutes for clarity
        SystemSetting::setValue('office_start_time', '09:00');
        SystemSetting::setValue('late_threshold_minutes', '15');

        // move time to 09:30 so it's past threshold
        Carbon::setTestNow(Carbon::parse('09:30'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-in', [
                'remarks' => 'Late arrival',
            ])
            ->assertOk();

        Carbon::setTestNow();

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'status' => 'late',
        ]);
    }

    public function test_checkout_calculates_total_minutes(): void
    {
        $user = User::factory()->create();

        $checkIn = now()->subHours(9);

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => $checkIn,
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-out')
            ->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
        ]);

        $this->assertNotNull($record->fresh()->total_minutes);
    }

    public function test_checkout_marks_half_day_when_insufficient_hours(): void
    {
        $user = User::factory()->create();

        // create a short work session
        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subMinutes(100),
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-out')
            ->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'status' => 'half_day',
        ]);
    }

    public function test_employee_cannot_check_out_twice(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
            'check_out' => now()->subHours(1),
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/attendance/check-out')
            ->assertUnprocessable();
    }

    public function test_authenticated_user_can_list_attendance(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
        ]);

        AttendanceRecord::create([
            'user_id' => $other->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_only_see_own_records_in_list(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
        ]);

        AttendanceRecord::create([
            'user_id' => $other->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->json('data');

        $this->assertIsArray($resp);
        foreach ($resp as $item) {
            $this->assertEquals($user->id, $item['user_id']);
        }
    }

    public function test_employee_cannot_adjust_attendance(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $record = AttendanceRecord::create([
            'user_id' => $other->id,
            'attendance_date' => today(),
            'check_in' => now()->subHours(8),
            'status' => 'present',
        ]);

        $token = PersonalAccessToken::createTokenFor($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/attendance/'.$record->id, [
                'remarks' => 'Attempted adjust',
            ])
            ->assertForbidden();
    }
}
