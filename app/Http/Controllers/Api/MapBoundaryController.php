<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\Ward;
use App\Models\WardReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MapBoundaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = "map_boundaries_{$user->role}_{$user->lga_id}";

        $data = Cache::remember($cacheKey, 300, function () use ($user) {
            $thresholds = config('jesr.map_rag_thresholds');

            // Get IDs of polling stations that have at least one report
            $reportedPuIds = WardReport::query()
                ->forUser($user)
                ->whereNotNull('polling_station_id')
                ->distinct()
                ->pluck('polling_station_id');

            // LGA boundaries — scoped by role
            $lgaQuery = Lga::whereNotNull('boundary_geojson');
            if ($user->isStateAdmin() && $user->state_id) {
                $lgaQuery->where('state_id', $user->state_id);
            } elseif ($user->isSupervisor() && $user->lga_id) {
                $lgaQuery->where('id', $user->lga_id);
            }

            $lgas = $lgaQuery
                ->withCount(['pollingStations as total_pus' => fn($q) => $q->where('is_active', true)])
                ->get()
                ->map(function ($lga) use ($reportedPuIds, $thresholds) {
                    $reporting = $lga->pollingStations()
                        ->active()
                        ->whereIn('id', $reportedPuIds)
                        ->count();

                    $coverage = $lga->total_pus > 0
                        ? round(($reporting / $lga->total_pus) * 100, 1)
                        : 0;

                    return [
                        'id'       => $lga->id,
                        'name'     => $lga->name,
                        'code'     => $lga->code,
                        'geojson'  => $lga->boundary_geojson,
                        'total_pus' => $lga->total_pus,
                        'reporting' => $reporting,
                        'coverage'  => $coverage,
                        'rag'       => $this->computeRag($coverage, $thresholds),
                    ];
                });

            // Ward boundaries
            $wardQuery = Ward::whereNotNull('boundary_geojson')
                ->withCount(['pollingStations as total_pus' => fn($q) => $q->where('is_active', true)]);

            if ($user->isSupervisor()) {
                $wardQuery->where('lga_id', $user->lga_id);
            }

            $wards = $wardQuery->get()
                ->map(function ($ward) use ($reportedPuIds, $thresholds) {
                    $reporting = $ward->pollingStations()
                        ->active()
                        ->whereIn('id', $reportedPuIds)
                        ->count();

                    $coverage = $ward->total_pus > 0
                        ? round(($reporting / $ward->total_pus) * 100, 1)
                        : 0;

                    return [
                        'id'       => $ward->id,
                        'name'     => $ward->name,
                        'code'     => $ward->code,
                        'lga_id'   => $ward->lga_id,
                        'geojson'  => $ward->boundary_geojson,
                        'total_pus' => $ward->total_pus,
                        'reporting' => $reporting,
                        'coverage'  => $coverage,
                        'rag'       => $this->computeRag($coverage, $thresholds),
                    ];
                });

            return ['lgas' => $lgas, 'wards' => $wards];
        });

        return response()->json($data);
    }

    private function computeRag(float $coverage, array $thresholds): string
    {
        if ($coverage >= $thresholds['green']) return 'green';
        if ($coverage >= $thresholds['amber']) return 'amber';
        return 'red';
    }
}
