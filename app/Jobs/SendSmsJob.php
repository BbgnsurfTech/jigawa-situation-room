<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min exponential backoff

    public function __construct(
        private string $to,
        private string $message,
        private int $smsMessageId,
    ) {}

    public function handle(): void
    {
        $record = SmsMessage::find($this->smsMessageId);

        if (!$record) {
            return;
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post('https://api.africastalking.com/version1/messaging', [
                'headers' => [
                    'apiKey' => config('jesr.africastalking.api_key'),
                    'Accept' => 'application/json',
                ],
                'form_params' => [
                    'username' => config('jesr.africastalking.username'),
                    'to'       => $this->to,
                    'message'  => $this->message,
                    'from'     => config('jesr.africastalking.sender_id'),
                ],
            ]);

            $data   = json_decode($response->getBody(), true);
            $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? 'unknown';

            $record->update([
                'delivery_status' => $status === 'Success' ? 'delivered' : 'failed',
                'retry_count'     => $this->attempts(),
            ]);
        } catch (\Exception $e) {
            Log::error("[SMS JOB] Attempt {$this->attempts()} failed to {$this->to}: " . $e->getMessage());
            $record->increment('retry_count');
            $record->update(['delivery_status' => 'failed']);
            $this->fail($e);
        }
    }

    public function failed(\Throwable $e): void
    {
        $record = SmsMessage::find($this->smsMessageId);
        $record?->update(['delivery_status' => 'failed']);
        Log::error("[SMS JOB] Permanently failed to {$this->to} after {$this->tries} attempts: " . $e->getMessage());
    }
}
