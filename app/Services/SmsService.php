<?php
namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        $record = SmsMessage::create([
            'direction' => 'outbound',
            'from_phone' => config('jesr.africastalking.sender_id', 'JESR'),
            'to_phone' => $to,
            'body' => $message,
            'delivery_status' => 'pending',
            'sent_at' => now(),
        ]);

        if (config('jesr.sms_driver') === 'sandbox') {
            Log::channel('single')->info("[SMS SANDBOX] To: {$to} | Message: {$message}");
            $record->update(['delivery_status' => 'delivered']);
            return true;
        }

        // Production: dispatch queued job with exponential-backoff retry
        SendSmsJob::dispatch($to, $message, $record->id);
        return true;
    }

    public function sendBulk(array $recipients, string $message): array
    {
        $results = [];
        foreach ($recipients as $phone) {
            $results[$phone] = $this->send($phone, $message);
        }
        return $results;
    }

    private function sendViaAfricasTalking(SmsMessage $record, string $to, string $message): bool
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://api.africastalking.com/version1/messaging', [
                'headers' => [
                    'apiKey' => config('jesr.africastalking.api_key'),
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'username' => config('jesr.africastalking.username'),
                    'to' => $to,
                    'message' => $message,
                    'from' => config('jesr.africastalking.sender_id'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? 'unknown';
            $record->update(['delivery_status' => $status === 'Success' ? 'sent' : 'failed']);
            return $status === 'Success';
        } catch (\Exception $e) {
            Log::error("[SMS] Send failed to {$to}: " . $e->getMessage());
            $record->increment('retry_count');
            $record->update(['delivery_status' => 'failed']);
            return false;
        }
    }
}
