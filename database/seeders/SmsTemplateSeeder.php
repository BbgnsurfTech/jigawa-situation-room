<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Report Reminder',
                'slug' => 'report-reminder',
                'message' => 'JESR: Please submit your ward report for today. Reply with your report or login to the app.',
                'category' => 'reminder',
                'description' => 'Sent to ward agents to remind them to submit reports.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Urgent Alert',
                'slug' => 'urgent-alert',
                'message' => 'JESR URGENT: Please check your dashboard immediately for critical updates requiring your attention.',
                'category' => 'alert',
                'description' => 'Sent when urgent action is needed.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Verification Needed',
                'slug' => 'verification-needed',
                'message' => 'JESR: Your submitted report requires verification. Please login to provide additional details.',
                'category' => 'action',
                'description' => 'Sent when a report needs clarification or additional information.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Password Reset',
                'slug' => 'password-reset',
                'message' => 'JESR: Your password has been reset. Please login with your new credentials.',
                'category' => 'account',
                'description' => 'Sent after a password reset.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Welcome Agent',
                'slug' => 'welcome-agent',
                'message' => 'JESR: Welcome {name}! You have been registered as a {role}. Login to start submitting reports.',
                'category' => 'onboarding',
                'description' => 'Sent when a new agent is registered.',
                'placeholders' => ['name', 'role'],
                'is_system' => true,
            ],
            [
                'name' => 'Login OTP',
                'slug' => 'login-otp',
                'message' => 'JESR: Your one-time password is {OTP}. Valid for 10 minutes. Do not share.',
                'category' => 'security',
                'description' => 'Sent for two-factor authentication.',
                'placeholders' => ['OTP'],
                'is_system' => true,
            ],
            [
                'name' => 'Incident Escalated',
                'slug' => 'incident-escalated',
                'message' => 'JESR: A security incident has been escalated in {location}. Check your dashboard for details.',
                'category' => 'alert',
                'description' => 'Sent when a security incident is escalated.',
                'placeholders' => ['location'],
                'is_system' => true,
            ],
            [
                'name' => 'Shift Reminder',
                'slug' => 'shift-reminder',
                'message' => 'JESR: Your monitoring shift starts in 1 hour. Please ensure you are ready at {location}.',
                'category' => 'reminder',
                'description' => 'Sent before a monitoring shift begins.',
                'placeholders' => ['location'],
                'is_system' => true,
            ],
            [
                'name' => 'Election Day Start',
                'slug' => 'election-day-start',
                'message' => 'JESR: Election day has begun. All agents should be at their assigned stations. Report any issues immediately.',
                'category' => 'election',
                'description' => 'Sent at the start of election day.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Voting Closed',
                'slug' => 'voting-closed',
                'message' => 'JESR: Voting has closed. Please submit your final report with vote counts within the next 30 minutes.',
                'category' => 'election',
                'description' => 'Sent when voting closes.',
                'placeholders' => null,
                'is_system' => true,
            ],
            [
                'name' => 'Report Received',
                'slug' => 'report-received',
                'message' => 'JESR: Your report for {ward} has been received and is pending verification. Thank you.',
                'category' => 'action',
                'description' => 'Confirmation sent after report submission.',
                'placeholders' => ['ward'],
                'is_system' => true,
            ],
            [
                'name' => 'Report Verified',
                'slug' => 'report-verified',
                'message' => 'JESR: Your report for {ward} has been verified. Thank you for your service.',
                'category' => 'action',
                'description' => 'Sent when a report is verified.',
                'placeholders' => ['ward'],
                'is_system' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, ['is_active' => true])
            );
        }

        $this->command->info('SmsTemplateSeeder: ' . count($templates) . ' SMS templates seeded.');
    }
}
