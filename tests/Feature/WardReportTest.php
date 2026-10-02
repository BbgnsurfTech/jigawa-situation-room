<?php

namespace Tests\Feature;

use App\Events\ReportSubmitted;
use App\Models\Lga;
use App\Models\User;
use App\Models\Ward;
use App\Models\WardReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WardReportTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private User $supervisor;
    private User $admin;
    private Lga $lga;
    private Ward $ward;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndUserSeeder::class);

        $this->lga = Lga::factory()->create();
        $this->ward = Ward::factory()->create(['lga_id' => $this->lga->id]);

        $this->agent = User::factory()->create([
            'role' => 'ward_agent',
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
        ]);
        $this->supervisor = User::factory()->create([
            'role' => 'supervisor',
            'lga_id' => $this->lga->id,
        ]);
        $this->admin = User::where('phone', '08000000001')->first();
    }

    private function validReportPayload(array $overrides = []): array
    {
        return array_merge([
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
            'counted_voter_papers' => 500,
            'security_present' => false,
            'number_cast_votes' => 300,
            'voting_papers_left' => 200,
            'valid_ballot_papers' => 280,
            'invalid_ballot_papers' => 20,
            'submitted_via' => 'web',
        ], $overrides);
    }

    public function test_agent_can_submit_report(): void
    {
        Event::fake([ReportSubmitted::class]);

        $response = $this->actingAs($this->agent)
            ->postJson('/api/v1/reports', $this->validReportPayload());

        $response->assertCreated()
            ->assertJsonPath('status', 'submitted');

        $this->assertDatabaseHas('ward_reports', [
            'agent_user_id' => $this->agent->id,
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
        ]);

        Event::assertDispatched(ReportSubmitted::class);
    }

    public function test_ballot_cross_field_validation_fails_when_valid_plus_invalid_exceeds_cast(): void
    {
        $this->actingAs($this->agent)
            ->postJson('/api/v1/reports', $this->validReportPayload([
                'number_cast_votes' => 300,
                'valid_ballot_papers' => 280,
                'invalid_ballot_papers' => 50, // 280+50=330 > 300
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('valid_ballot_papers');
    }

    public function test_security_types_required_when_security_present(): void
    {
        $this->actingAs($this->agent)
            ->postJson('/api/v1/reports', $this->validReportPayload([
                'security_present' => true,
                // security_types missing
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('security_types');
    }

    public function test_report_with_security_present_creates_in_app_notifications(): void
    {
        $this->supervisor; // ensure supervisor exists for this LGA

        $this->actingAs($this->agent)
            ->postJson('/api/v1/reports', $this->validReportPayload([
                'security_present' => true,
                'security_types' => ['ARMY', 'POLICE'],
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('jesr_notifications', [
            'recipient_user_id' => $this->supervisor->id,
            'type' => 'security_incident',
        ]);
    }

    public function test_agent_can_only_see_own_reports(): void
    {
        $otherAgent = User::factory()->create([
            'role' => 'ward_agent',
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
        ]);

        WardReport::factory()->create(['agent_user_id' => $this->agent->id, 'lga_id' => $this->lga->id, 'ward_id' => $this->ward->id]);
        WardReport::factory()->create(['agent_user_id' => $otherAgent->id, 'lga_id' => $this->lga->id, 'ward_id' => $this->ward->id]);

        $response = $this->actingAs($this->agent)
            ->getJson('/api/v1/reports');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->agent->id, $data[0]['agent_user_id']);
    }

    public function test_supervisor_can_only_see_lga_reports(): void
    {
        $otherLga = Lga::factory()->create();
        $otherWard = Ward::factory()->create(['lga_id' => $otherLga->id]);
        $otherAgent = User::factory()->create(['role' => 'ward_agent', 'lga_id' => $otherLga->id, 'ward_id' => $otherWard->id]);

        WardReport::factory()->create(['agent_user_id' => $this->agent->id, 'lga_id' => $this->lga->id, 'ward_id' => $this->ward->id]);
        WardReport::factory()->create(['agent_user_id' => $otherAgent->id, 'lga_id' => $otherLga->id, 'ward_id' => $otherWard->id]);

        $response = $this->actingAs($this->supervisor)
            ->getJson('/api/v1/reports');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->lga->id, $data[0]['lga_id']);
    }

    public function test_super_admin_can_see_all_reports(): void
    {
        $otherLga = Lga::factory()->create();
        $otherWard = Ward::factory()->create(['lga_id' => $otherLga->id]);
        $otherAgent = User::factory()->create(['role' => 'ward_agent', 'lga_id' => $otherLga->id, 'ward_id' => $otherWard->id]);

        WardReport::factory()->create(['agent_user_id' => $this->agent->id, 'lga_id' => $this->lga->id, 'ward_id' => $this->ward->id]);
        WardReport::factory()->create(['agent_user_id' => $otherAgent->id, 'lga_id' => $otherLga->id, 'ward_id' => $otherWard->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/reports');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_agent_can_update_report_within_correction_window(): void
    {
        $report = WardReport::factory()->create([
            'agent_user_id' => $this->agent->id,
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->agent)
            ->putJson("/api/v1/reports/{$report->id}", [
                'valid_ballot_papers' => 250,
                'invalid_ballot_papers' => 20,
                'correction_reason' => 'Typo in original submission',
            ])
            ->assertOk();

        $this->assertDatabaseHas('ward_reports', [
            'id' => $report->id,
            'valid_ballot_papers' => 250,
        ]);
    }

    public function test_agent_cannot_update_report_after_correction_window(): void
    {
        $report = WardReport::factory()->create([
            'agent_user_id' => $this->agent->id,
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
            'created_at' => now()->subMinutes(15), // past the 10-min window
        ]);

        $this->actingAs($this->agent)
            ->putJson("/api/v1/reports/{$report->id}", [
                'valid_ballot_papers' => 250,
                'correction_reason' => 'Late correction attempt',
            ])
            ->assertForbidden();
    }

    public function test_supervisor_can_verify_report(): void
    {
        $report = WardReport::factory()->create([
            'agent_user_id' => $this->agent->id,
            'lga_id' => $this->lga->id,
            'ward_id' => $this->ward->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->supervisor)
            ->postJson("/api/v1/reports/{$report->id}/verify")
            ->assertOk();

        $this->assertDatabaseHas('ward_reports', [
            'id' => $report->id,
            'status' => 'verified',
        ]);
    }

    public function test_batch_submission_creates_multiple_reports(): void
    {
        $payload = [
            'reports' => [
                $this->validReportPayload(),
                $this->validReportPayload(['remarks' => 'Second batch report']),
            ],
        ];

        $this->actingAs($this->agent)
            ->postJson('/api/v1/reports/batch', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success')
            ->assertJsonPath('results.1.status', 'success');

        $this->assertDatabaseCount('ward_reports', 2);
    }
}
