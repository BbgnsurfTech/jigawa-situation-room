<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use App\Models\WardReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndUserSeeder::class);
    }

    public function test_audit_log_is_created_on_report_submission(): void
    {
        $lga = Lga::factory()->create();
        $ward = Ward::factory()->create(['lga_id' => $lga->id]);
        $agent = User::factory()->create(['role' => 'ward_agent', 'lga_id' => $lga->id, 'ward_id' => $ward->id]);

        $this->actingAs($agent)->postJson('/api/v1/reports', [
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'counted_voter_papers' => 500,
            'security_present' => false,
            'number_cast_votes' => 300,
            'voting_papers_left' => 200,
            'valid_ballot_papers' => 280,
            'invalid_ballot_papers' => 20,
            'submitted_via' => 'web',
        ])->assertCreated();

        // Observer should have created an audit log
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $agent->id,
            'action_type' => 'create',
            'entity_type' => 'WardReport',
        ]);
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => 1,
            'actor_role' => 'super_admin',
            'action_type' => 'login',
            'entity_type' => 'User',
            'entity_id' => 1,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are immutable');

        $log->update(['action_type' => 'tampered']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => 1,
            'actor_role' => 'super_admin',
            'action_type' => 'login',
            'entity_type' => 'User',
            'entity_id' => 1,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are immutable');

        $log->delete();
    }

    public function test_login_creates_audit_log(): void
    {
        // Super admin has MFA — complete the flow to trigger the login audit log
        $mfaResponse = $this->postJson('/api/v1/auth/login', [
            'phone'    => '08000000001',
            'password' => 'Admin@JESR2024',
        ])->assertOk();

        $admin = User::where('phone', '08000000001')->first();
        $otp   = $admin->fresh()->mfa_otp;

        $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_token' => $mfaResponse->json('mfa_token'),
            'otp'       => $otp,
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'login',
            'entity_type' => 'User',
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::where('phone', '08000000001')->first();

        AuditLog::create([
            'actor_user_id' => $admin->id,
            'actor_role' => 'super_admin',
            'action_type' => 'login',
            'entity_type' => 'User',
            'entity_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/audit')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
