<?php

namespace Tests\Feature;

use App\Events\AgentLoggedIn;
use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndUserSeeder::class);
    }

    public function test_super_admin_can_login(): void
    {
        // Step 1: Login triggers MFA for super admin
        $mfaResponse = $this->postJson('/api/v1/auth/login', [
            'phone'    => '08000000001',
            'password' => 'Admin@JESR2024',
        ]);

        $mfaResponse->assertOk()->assertJsonPath('mfa_required', true);
        $mfaToken = $mfaResponse->json('mfa_token');

        // Step 2: Retrieve OTP from DB and verify
        $admin = \App\Models\User::where('phone', '08000000001')->first();
        $otp   = $admin->fresh()->mfa_otp;
        $this->assertNotNull($otp);

        $this->postJson('/api/v1/auth/mfa/verify', [
            'mfa_token' => $mfaToken,
            'otp'       => $otp,
        ])->assertOk()
          ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user'])
          ->assertJsonPath('user.role', 'super_admin');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'phone' => '08000000001',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('phone', '08000000001')->first();
        $user->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '08000000001',
            'password' => 'Admin@JESR2024',
        ])->assertForbidden();
    }

    public function test_ward_agent_device_binding_on_first_login(): void
    {
        $lga = Lga::factory()->create();
        $ward = Ward::factory()->create(['lga_id' => $lga->id]);
        $agent = User::factory()->create([
            'role' => 'ward_agent',
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'device_id' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => $agent->phone,
            'password' => 'password',
            'device_id' => 'DEVICE-ABC-123',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $agent->id,
            'device_id' => 'DEVICE-ABC-123',
        ]);
    }

    public function test_ward_agent_blocked_on_unregistered_device(): void
    {
        $lga = Lga::factory()->create();
        $ward = Ward::factory()->create(['lga_id' => $lga->id]);
        $agent = User::factory()->create([
            'role' => 'ward_agent',
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'device_id' => 'REGISTERED-DEVICE',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => $agent->phone,
            'password' => 'password',
            'device_id' => 'DIFFERENT-DEVICE',
        ])->assertForbidden()
            ->assertJsonPath('requires_verification', true);
    }

    public function test_agent_login_broadcasts_event(): void
    {
        Event::fake([AgentLoggedIn::class]);

        $lga = Lga::factory()->create();
        $ward = Ward::factory()->create(['lga_id' => $lga->id]);
        $agent = User::factory()->create([
            'role' => 'ward_agent',
            'lga_id' => $lga->id,
            'ward_id' => $ward->id,
            'device_id' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => $agent->phone,
            'password' => 'password',
        ])->assertOk();

        Event::assertDispatched(AgentLoggedIn::class, fn($e) => $e->agent->id === $agent->id);
    }

    public function test_non_agent_login_does_not_broadcast_agent_event(): void
    {
        Event::fake([AgentLoggedIn::class]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => '08000000001',
            'password' => 'Admin@JESR2024',
        ])->assertOk();

        Event::assertNotDispatched(AgentLoggedIn::class);
    }

    public function test_logout_deletes_token(): void
    {
        $user = User::where('phone', '08000000001')->first();
        $token = $user->createToken('test');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_endpoint_returns_authenticated_user(): void
    {
        $user = User::where('phone', '08000000001')->first();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('full_name', $user->full_name);
    }
}
