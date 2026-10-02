<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function __construct(private ReportExportService $exportService) {}

    public function export(Request $request, string $type): mixed
    {
        if (!in_array($type, ReportExportService::REPORT_TYPES)) {
            return response()->json(['message' => 'Invalid report type.'], 422);
        }

        $filters = $request->only(['lga_id', 'ward_id', 'party_id', 'from', 'to', 'status', 'submitted_via']);
        $format = $request->input('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportService->exportCsv($type, $filters, $request->user());
        }

        return $this->exportService->exportPdf($type, $filters, $request->user());
    }
}
