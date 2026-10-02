<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\Ward;
use App\Services\SmsParser;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(
        private SmsService $smsService,
        private SmsParser $smsParser,
    ) {}

    // Inbound webhook from Africa's Talking — verified by username field
    public function receive(Request $request): JsonResponse
    {
        // SEC-01: Verify request originates from our Africa's Talking account.
        // AT sends the configured username with every webhook POST.
        $expectedUsername = config('jesr.africastalking.username');
        if ($expectedUsername && $request->input('username') !== $expectedUsername) {
            return response()->json(['status' => 'unauthorized'], 403);
        }

        $from = $request->input('from') ?? $request->input('phoneNumber', '');
        $body = $request->input('text') ?? $request->input('Text', '');

        if (empty($from) || empty($body)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $registered = User::where('phone', $from)->where('is_active', true)->exists();

        $sms = SmsMessage::create([
            'direction'       => 'inbound',
            'from_phone'      => $from,
            'to_phone'        => config('jesr.africastalking.sender_id', 'JESR'),
            'body'            => $body,
            'delivery_status' => 'delivered',
            'sent_at'         => now(),
        ]);

        if (!$registered) {
            return response()->json(['status' => 'received'], 200);
        }

        $result = $this->smsParser->parse($sms);

        if ($result['success']) {
            $this->smsService->send($from, "CONF IR {$result['report_id']} Received. Thank you.");
        } else {
            $this->smsService->send($from, $this->smsParser->formatError($result['error']));
        }

        return response()->json(['status' => 'processed'], 200);
    }

    // Outbound SMS — admin/supervisor only
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'recipients'   => ['required_without:group', 'array'],
            'recipients.*' => ['string'],
            'message'      => ['required', 'string', 'max:160'],
            'group'        => ['nullable', 'in:all_agents,by_lga,by_ward'],
            'lga_id'       => ['nullable', 'exists:lgas,id'],
            'ward_id'      => ['nullable', 'exists:wards,id'],
        ]);

        $recipients = $request->input('recipients', []);

        // Resolve group targets
        $group = $request->input('group');
        if ($group === 'all_agents') {
            $recipients = User::where('role', 'ward_agent')
                ->where('is_active', true)
                ->pluck('phone')->toArray();
        } elseif ($group === 'by_lga' && $request->lga_id) {
            $recipients = User::where('role', 'ward_agent')
                ->where('lga_id', $request->lga_id)
                ->where('is_active', true)
                ->pluck('phone')->toArray();
        } elseif ($group === 'by_ward' && $request->ward_id) {
            $recipients = User::where('role', 'ward_agent')
                ->where('ward_id', $request->ward_id)
                ->where('is_active', true)
                ->pluck('phone')->toArray();
        }

        if (empty($recipients)) {
            return response()->json(['message' => 'No recipients found.'], 422);
        }

        $results = $this->smsService->sendBulk($recipients, $request->message);
        $sent    = count(array_filter($results));

        return response()->json([
            'message' => "SMS sent to {$sent}/" . count($results) . " recipients.",
            'results' => $results,
        ]);
    }
}
