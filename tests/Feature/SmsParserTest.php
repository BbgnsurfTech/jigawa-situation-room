<?php

namespace Tests\Feature;

use App\Models\Lga;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\Ward;
use App\Services\SmsParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsParserTest extends TestCase
{
    use RefreshDatabase;

    private SmsParser $parser;
    private Ward $ward;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = app(SmsParser::class);

        $this->seed(\Database\Seeders\RoleAndUserSeeder::class);

        $lga = Lga::factory()->create();
        $this->ward = Ward::factory()->create([
            'lga_id' => $lga->id,
            'code' => 'AUY001',
        ]);
        $this->agent = User::factory()->create([
            'role' => 'ward_agent',
            'phone' => '08012345678',
            'lga_id' => $lga->id,
            'ward_id' => $this->ward->id,
            'party_affiliation' => 'APC',
        ]);
    }

    private function makeSmsMessage(string $from, string $body): SmsMessage
    {
        return SmsMessage::create([
            'direction' => 'inbound',
            'from_phone' => $from,
            'to_phone' => '12345',
            'body' => $body,
            'delivery_status' => 'delivered',
        ]);
    }

    public function test_valid_sms_creates_ward_report(): void
    {
        $sms = $this->makeSmsMessage('08012345678', 'JIG REPORT AUY001 AB APC 500 280 20 N');

        $result = $this->parser->parse($sms);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('ward_reports', [
            'ward_id' => $this->ward->id,
            'agent_user_id' => $this->agent->id,
            'submitted_via' => 'sms',
            'valid_ballot_papers' => 280,
            'invalid_ballot_papers' => 20,
            'security_present' => false,
        ]);
    }

    public function test_sms_with_security_present(): void
    {
        $sms = $this->makeSmsMessage('08012345678', 'JIG REPORT AUY001 AB APC 500 280 20 Y ARMY');

        $result = $this->parser->parse($sms);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('ward_reports', [
            'ward_id' => $this->ward->id,
            'security_present' => true,
        ]);
    }

    public function test_sms_with_invalid_ward_code_fails(): void
    {
        $sms = $this->makeSmsMessage('08012345678', 'JIG REPORT XXXINVALID AB APC 500 280 20 N');

        $result = $this->parser->parse($sms);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ward', strtolower($result['error']));
    }

    public function test_sms_from_unknown_phone_fails(): void
    {
        $sms = $this->makeSmsMessage('09099999999', 'JIG REPORT AUY001 AB APC 500 280 20 N');

        $result = $this->parser->parse($sms);

        $this->assertFalse($result['success']);
        $this->assertEquals('unregistered_phone', $result['error']);
    }

    public function test_sms_with_ballot_math_error_fails(): void
    {
        // valid(400) + invalid(150) = 550 > cast(500)
        $sms = $this->makeSmsMessage('08012345678', 'JIG REPORT AUY001 AB APC 500 400 150 N');

        $result = $this->parser->parse($sms);

        $this->assertFalse($result['success']);
        $this->assertEquals('ballot_count_mismatch', $result['error']);
    }

    public function test_sms_webhook_endpoint_accepts_inbound(): void
    {
        // The controller receives raw POST data and creates SmsMessage then parses it
        // Include AT username header that SEC-01 now requires
        $this->postJson('/api/v1/sms/receive', [
            'username' => config('jesr.africastalking.username', 'sandbox'),
            'from'     => '08012345678',
            'text'     => 'JIG REPORT AUY001 AB APC 500 280 20 N',
        ])->assertOk();

        $this->assertDatabaseHas('ward_reports', [
            'submitted_via' => 'sms',
            'ward_id' => $this->ward->id,
        ]);
    }
}
