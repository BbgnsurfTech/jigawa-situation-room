<?php

namespace App\Services;

use App\Models\PollingUnitAgent;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PollingUnitAgentExportService
{
    public function exportPdf(array $filters, User $generator): \Illuminate\Http\Response
    {
        $agents = $this->getData($filters);
        $isSuperAdmin = $generator->isSuperAdmin();

        $pdf = Pdf::loadView('pdf.polling-unit-agents', [
            'agents' => $agents,
            'filters' => $filters,
            'isSuperAdmin' => $isSuperAdmin,
            'generated_at' => now()->format('d M Y, H:i:s'),
            'generator' => $generator,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('jesr-pu-agents-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(array $filters, User $generator): StreamedResponse
    {
        $agents = $this->getData($filters);
        $isSuperAdmin = $generator->isSuperAdmin();
        $filename = 'jesr-pu-agents-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($agents, $isSuperAdmin) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['JIGAWA STATE ELECTION SITUATION ROOM (JESR)']);
            fputcsv($handle, ['Polling Unit Agent Register']);
            fputcsv($handle, ['Generated: '.now()->format('d M Y H:i:s')]);
            fputcsv($handle, []);

            $headers = [
                'First Name', 'Surname', 'Polling Unit', 'PU Number',
                'LGA', 'Ward', 'State', 'NIN', 'Voters Card No.',
                'Leadership Roles', 'Bank Name',
            ];
            if ($isSuperAdmin) {
                $headers[] = 'BVN';
                $headers[] = 'Account Number';
            }
            fputcsv($handle, $headers);

            foreach ($agents as $agent) {
                $roles = collect($agent->leadership_roles ?? [])
                    ->map(fn ($r) => PollingUnitAgentService::LEADERSHIP_ROLES[$r] ?? $r)
                    ->implode(', ');

                $row = [
                    $agent->first_name,
                    $agent->surname,
                    $agent->polling_unit,
                    $agent->polling_unit_number,
                    $agent->lga?->name,
                    $agent->ward?->name,
                    $agent->state,
                    $agent->nin,
                    $agent->voters_card_number,
                    $roles,
                    $agent->bank_name,
                ];

                if ($isSuperAdmin) {
                    $row[] = $agent->getRawBvn();
                    $row[] = $agent->getRawAccountNumber();
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function getData(array $filters): \Illuminate\Support\Collection
    {
        return PollingUnitAgent::with(['lga', 'ward'])
            ->filtered($filters)
            ->orderBy('lga_id')
            ->orderBy('surname')
            ->get();
    }
}
