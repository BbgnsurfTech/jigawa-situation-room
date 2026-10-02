<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AuditLog::with('actor')->orderByDesc('created_at');

        // Scope by role
        if ($user->isSupervisor()) {
            // Supervisors see logs for their LGA's reports
            $lgaReportIds = \App\Models\WardReport::where('lga_id', $user->lga_id)->pluck('id');
            $query->where(function ($q) use ($lgaReportIds, $user) {
                $q->where('actor_user_id', $user->id)
                  ->orWhere(function ($q2) use ($lgaReportIds) {
                      $q2->where('entity_type', 'WardReport')
                         ->whereIn('entity_id', $lgaReportIds);
                  });
            });
        }

        if ($request->entity_type) $query->where('entity_type', $request->entity_type);
        if ($request->user_id) $query->where('actor_user_id', $request->user_id);
        if ($request->action_type) $query->where('action_type', $request->action_type);
        if ($request->from) $query->whereDate('created_at', '>=', $request->from);
        if ($request->to) $query->whereDate('created_at', '<=', $request->to);

        return response()->json($query->paginate($request->input('per_page', 50)));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::with('actor')->orderByDesc('created_at');

        if ($request->from) $query->whereDate('created_at', '>=', $request->from);
        if ($request->to) $query->whereDate('created_at', '<=', $request->to);
        if ($request->entity_type) $query->where('entity_type', $request->entity_type);
        if ($request->user_id) $query->where('actor_user_id', $request->user_id);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="jesr-audit-log-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Actor', 'Role', 'Action', 'Entity', 'Entity ID', 'IP', 'Timestamp', 'Reason']);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->actor?->full_name ?? 'System',
                        $log->actor_role,
                        $log->action_type,
                        $log->entity_type,
                        $log->entity_id,
                        $log->ip_address,
                        $log->created_at->toISOString(),
                        $log->reason ?? '',
                    ]);
                }
            });
            fclose($handle);
        }, 'jesr-audit-log.csv', $headers);
    }
}
