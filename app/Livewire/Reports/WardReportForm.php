<?php
namespace App\Livewire\Reports;

use App\Http\Requests\StoreReportRequest;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Lga;
use App\Models\Party;
use App\Models\PollingStation;
use App\Models\Ward;
use App\Services\ReportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class WardReportForm extends Component
{
    use WithFileUploads;

    // Part A: Station Info
    public ?int $lga_id = null;
    public ?int $ward_id = null;
    public ?int $polling_station_id = null;
    public string $inec_agent_name = '';
    public string $party_agent_id = '';

    // Part B: Voter Papers
    public string $counted_voter_papers = '';
    public string $voting_closed_time = '';

    // Part C: Ballot Count
    public string $number_cast_votes = '';
    public string $voting_papers_left = '';
    public string $valid_ballot_papers = '';
    public string $invalid_ballot_papers = '';

    // Part D: Security
    public bool $security_present = false;
    public array $security_types = [];
    public string $remarks = '';

    // Part E: Party Results (PU-level)
    public string $ec8a_serial_number = '';
    public array $party_results = [];

    // PU context
    public ?int $registered_voter_count = null;
    public bool $is_pu_agent = false;

    public bool $submitted = false;
    public int $reportId = 0;
    public string $errorMessage = '';
    public array $warnings = [];

    protected array $availableSecurityTypes = ['POLICE', 'CIVIL_DEFENCE', 'NSCDC', 'ARMY', 'VIGILANTE', 'OTHER'];

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user->isFieldAgent()) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->lga_id  = $user->lga_id;
        $this->ward_id = $user->ward_id;

        if ($user->isPollingUnitAgent()) {
            $this->is_pu_agent = true;
            $this->polling_station_id = $user->polling_station_id;

            // Auto-derive ward/lga from station
            $station = $user->pollingStation;
            if ($station) {
                $this->ward_id = $station->ward_id;
                $this->lga_id  = $station->lga_id;
                $this->registered_voter_count = $station->registered_voter_count;
            }
        }
    }

    public function addPartyRow()
    {
        $this->party_results[] = ['election_type' => '', 'party_name' => '', 'votes_scored' => ''];
    }

    public function removePartyRow(int $index)
    {
        unset($this->party_results[$index]);
        $this->party_results = array_values($this->party_results);
    }

    public function getPartyVotesTotalProperty(): int
    {
        return collect($this->party_results)->sum(fn($r) => (int) ($r['votes_scored'] ?? 0));
    }

    public function getTurnoutRateProperty(): ?float
    {
        if (!$this->registered_voter_count || !$this->number_cast_votes) {
            return null;
        }
        return round(((int)$this->number_cast_votes / $this->registered_voter_count) * 100, 2);
    }

    public function getBallotWarning(): ?string
    {
        if ($this->valid_ballot_papers && $this->invalid_ballot_papers && $this->number_cast_votes) {
            $sum = (int)$this->valid_ballot_papers + (int)$this->invalid_ballot_papers;
            if ($sum > (int)$this->number_cast_votes) {
                return "Valid + Invalid ({$sum}) exceeds Cast Votes ({$this->number_cast_votes})";
            }
        }
        return null;
    }

    public function submit(ReportService $service)
    {
        $this->validate([
            'lga_id'                => 'required|integer',
            'ward_id'               => 'required|integer',
            'counted_voter_papers'  => 'required|integer|min:0',
            'number_cast_votes'     => 'required|integer|min:0',
            'voting_papers_left'    => 'required|integer|min:0',
            'valid_ballot_papers'   => 'required|integer|min:0',
            'invalid_ballot_papers' => 'required|integer|min:0',
            'security_types'        => 'required_if:security_present,true|array',
        ]);

        $data = [
            'lga_id'                => $this->lga_id,
            'ward_id'               => $this->ward_id,
            'polling_station_id'    => $this->polling_station_id,
            'inec_agent_name'       => $this->inec_agent_name ?: null,
            'party_agent_id'        => $this->party_agent_id ?: null,
            'counted_voter_papers'  => (int)$this->counted_voter_papers,
            'voting_closed_time'    => $this->voting_closed_time ?: null,
            'number_cast_votes'     => (int)$this->number_cast_votes,
            'voting_papers_left'    => (int)$this->voting_papers_left,
            'valid_ballot_papers'   => (int)$this->valid_ballot_papers,
            'invalid_ballot_papers' => (int)$this->invalid_ballot_papers,
            'security_present'      => $this->security_present,
            'security_types'        => $this->security_present ? $this->security_types : null,
            'remarks'               => $this->remarks ?: null,
            'submitted_via'         => 'web',
            'ec8a_serial_number'    => $this->ec8a_serial_number ?: null,
        ];

        // Include non-empty party results
        $validPartyResults = collect($this->party_results)
            ->filter(fn($r) => !empty($r['party_name']) && is_numeric($r['votes_scored'] ?? null))
            ->values()
            ->toArray();

        if (!empty($validPartyResults)) {
            $data['party_results'] = $validPartyResults;
        }

        try {
            $report = $service->createReport($data, auth()->user(), request());
            $this->reportId  = $report->id;
            $this->submitted = true;
            $this->dispatch('report-submitted', reportId: $report->id);
        } catch (\Exception $e) {
            $this->errorMessage = 'Submission failed. Please try again.';
        }
    }

    public function render()
    {
        $pollingStations = collect();
        if ($this->ward_id && !$this->is_pu_agent) {
            $pollingStations = PollingStation::where('ward_id', $this->ward_id)
                ->active()->orderBy('name')->get();
        }

        // Get active elections
        $activeElections = Election::active()->orderBy('name')->get();

        // Build candidate lookup: election_type => party_acronym => "Name"
        $candidateQuery = Candidate::with('party')
            ->where(function ($q) {
                $q->whereIn('post_type', ['presidential', 'governorship', 'senate'])
                    ->orWhere('lga_id', $this->lga_id)
                    ->orWhere('ward_id', $this->ward_id);
            });

        $candidateLookup = [];
        foreach ($candidateQuery->get() as $c) {
            $candidateLookup[$c->post_type][$c->party?->acronym] = $c->name;
        }

        return view('livewire.reports.ward-report-form', [
            'lgas'                   => Lga::orderBy('name')->get(),
            'wards'                  => $this->lga_id ? Ward::where('lga_id', $this->lga_id)->orderBy('name')->get() : collect(),
            'pollingStations'        => $pollingStations,
            'availableSecurityTypes' => $this->availableSecurityTypes,
            'ballotWarning'          => $this->getBallotWarning(),
            'parties'                => Party::orderBy('acronym')->get(),
            'candidateLookup'        => $candidateLookup,
            'activeElections'        => $activeElections,
            'electionTypes'          => Election::TYPES,
        ])->layout('layouts.app', ['title' => 'Submit Report']);
    }
}
