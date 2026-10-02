<?php
namespace App\Services;

use App\Models\PollingStation;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\Ward;
use App\Models\WardReport;

class SmsParser
{
    // Ward format: JIG REPORT <WardCode> <Initials> <Party> <Counted> <Valid> <Invalid> <Y/N> [SecCodes]
    // PU format:   JIG PU <PUCode> <Initials> <Party> <Counted> <Valid> <Invalid> <Y/N> [SecCodes]

    private const VALID_SEC_CODES = ['POLICE', 'NSCDC', 'ARMY', 'VIG', 'VIGILANTE', 'CIVIL_DEFENCE', 'OTHER'];

    public function parse(SmsMessage $smsMessage): array
    {
        $body = strtoupper(trim($smsMessage->body));

        // Detect format: PU or Ward
        if (str_starts_with($body, 'JIG PU ')) {
            return $this->parsePuFormat($body, $smsMessage);
        }

        if (str_starts_with($body, 'JIG REPORT ')) {
            return $this->parseWardFormat($body, $smsMessage);
        }

        return ['success' => false, 'error' => 'not_jesr_format'];
    }

    private function parseWardFormat(string $body, SmsMessage $smsMessage): array
    {
        $tokens = preg_split('/\s+/', trim(substr($body, 11)));

        if (count($tokens) < 7) {
            return ['success' => false, 'error' => 'insufficient_tokens', 'token_count' => count($tokens)];
        }

        [$wardCode, $agentInitials, $partyCode, $countedPapers, $valid, $invalid, $secFlag] = $tokens;
        $secCodes = isset($tokens[7]) ? explode(',', $tokens[7]) : [];

        $ward = Ward::where('code', $wardCode)->first();
        if (!$ward) {
            return ['success' => false, 'error' => 'invalid_ward_code', 'code' => $wardCode];
        }

        // Accept both ward_agent and polling_unit_agent
        $agent = User::where('phone', $smsMessage->from_phone)
            ->whereIn('role', ['ward_agent', 'polling_unit_agent'])
            ->where('is_active', true)
            ->first();

        if (!$agent) {
            return ['success' => false, 'error' => 'unregistered_phone'];
        }

        return $this->validateAndCreateReport(
            $countedPapers, $valid, $invalid, $secFlag, $secCodes,
            $agent, $ward->lga_id, $ward->id, null, $smsMessage
        );
    }

    private function parsePuFormat(string $body, SmsMessage $smsMessage): array
    {
        $tokens = preg_split('/\s+/', trim(substr($body, 7))); // strip "JIG PU "

        if (count($tokens) < 7) {
            return ['success' => false, 'error' => 'insufficient_tokens', 'token_count' => count($tokens)];
        }

        [$puCode, $agentInitials, $partyCode, $countedPapers, $valid, $invalid, $secFlag] = $tokens;
        $secCodes = isset($tokens[7]) ? explode(',', $tokens[7]) : [];

        $station = PollingStation::where('code', $puCode)->first();
        if (!$station) {
            return ['success' => false, 'error' => 'invalid_pu_code', 'code' => $puCode];
        }

        // Accept both ward_agent and polling_unit_agent
        $agent = User::where('phone', $smsMessage->from_phone)
            ->whereIn('role', ['ward_agent', 'polling_unit_agent'])
            ->where('is_active', true)
            ->first();

        if (!$agent) {
            return ['success' => false, 'error' => 'unregistered_phone'];
        }

        return $this->validateAndCreateReport(
            $countedPapers, $valid, $invalid, $secFlag, $secCodes,
            $agent, $station->lga_id, $station->ward_id, $station->id, $smsMessage
        );
    }

    private function validateAndCreateReport(
        string $countedPapers, string $valid, string $invalid, string $secFlag,
        array $secCodes, User $agent, int $lgaId, int $wardId,
        ?int $pollingStationId, SmsMessage $smsMessage
    ): array {
        if (!is_numeric($countedPapers) || !is_numeric($valid) || !is_numeric($invalid)) {
            return ['success' => false, 'error' => 'invalid_numeric_fields'];
        }

        $securityPresent = $secFlag === 'Y';
        $securityTypes = [];
        if ($securityPresent && !empty($secCodes)) {
            foreach ($secCodes as $code) {
                $code = trim($code);
                if (in_array($code, self::VALID_SEC_CODES)) {
                    $securityTypes[] = $code;
                }
            }
        }

        $validInt   = (int) $valid;
        $invalidInt = (int) $invalid;
        $castInt    = (int) $countedPapers;

        if (($validInt + $invalidInt) > $castInt) {
            return ['success' => false, 'error' => 'ballot_count_mismatch'];
        }

        // Compute turnout for PU reports
        $turnoutRate = null;
        $registeredSnapshot = null;
        if ($pollingStationId) {
            $station = PollingStation::find($pollingStationId);
            if ($station && $station->registered_voter_count > 0) {
                $registeredSnapshot = $station->registered_voter_count;
                $turnoutRate = round(($castInt / $registeredSnapshot) * 100, 2);
            }
        }

        $report = WardReport::create([
            'agent_user_id'                  => $agent->id,
            'lga_id'                         => $lgaId,
            'ward_id'                        => $wardId,
            'polling_station_id'             => $pollingStationId,
            'counted_voter_papers'           => $castInt,
            'security_present'               => $securityPresent,
            'security_types'                 => $securityTypes ?: null,
            'number_cast_votes'              => $castInt,
            'voting_papers_left'             => 0,
            'valid_ballot_papers'            => $validInt,
            'invalid_ballot_papers'          => $invalidInt,
            'status'                         => 'submitted',
            'submitted_via'                  => 'sms',
            'sync_status'                    => 'synced',
            'turnout_rate'                   => $turnoutRate,
            'registered_voter_count_snapshot' => $registeredSnapshot,
        ]);

        $smsMessage->update(['parsed_report_id' => $report->id]);

        return ['success' => true, 'report_id' => $report->id, 'agent' => $agent];
    }

    public function formatError(string $errorCode): string
    {
        return match($errorCode) {
            'not_jesr_format'       => 'JESR: Invalid format. Use: JIG REPORT <WardCode> ... or JIG PU <PUCode> ...',
            'insufficient_tokens'   => 'JESR: Incomplete message. Please check format and resend.',
            'invalid_ward_code'     => 'JESR: Ward code not found. Please check and resend.',
            'invalid_pu_code'       => 'JESR: Polling unit code not found. Please check and resend.',
            'unregistered_phone'    => 'JESR: Phone not registered. Contact your supervisor.',
            'invalid_numeric_fields'=> 'JESR: Invalid numbers detected. Use whole numbers only.',
            'ballot_count_mismatch' => 'JESR: Valid + Invalid votes exceed total counted. Please correct and resend.',
            default                 => 'JESR: Message could not be processed. Use the mobile app or contact support.',
        };
    }
}
