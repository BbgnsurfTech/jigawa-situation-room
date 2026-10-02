<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'lga_id' => ['required', 'exists:lgas,id'],
            'ward_id' => ['required', 'exists:wards,id'],
            'polling_station_id' => ['nullable', 'exists:polling_stations,id'],
            'ec8a_serial_number' => ['nullable', 'string', 'max:100'],
            'party_agent_id' => ['nullable', 'exists:users,id'],
            'inec_agent_name' => ['nullable', 'string', 'max:255'],
            'counted_voter_papers' => ['required', 'integer', 'min:0'],
            'security_present' => ['required', 'boolean'],
            'security_types' => [
                Rule::requiredIf(fn() => (bool) $this->input('security_present')),
                'nullable', 'array', 'min:1',
            ],
            'security_types.*' => [
                Rule::in(['POLICE', 'CIVIL_DEFENCE', 'NSCDC', 'ARMY', 'VIGILANTE', 'OTHER']),
            ],
            'voting_closed_time' => ['nullable', 'date_format:H:i'],
            'number_cast_votes' => ['required', 'integer', 'min:0'],
            'voting_papers_left' => ['required', 'integer', 'min:0'],
            'valid_ballot_papers' => ['required', 'integer', 'min:0'],
            'invalid_ballot_papers' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'submitted_via' => ['nullable', Rule::in(['web', 'android', 'ios', 'sms'])],
            'device_id' => ['nullable', 'string'],
            'submitted_at_device' => ['nullable', 'date'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png', 'max:10240'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi', 'max:51200'],
            'audio' => ['nullable', 'file', 'mimes:mp3,wav,m4a', 'max:20480'],
            'party_results' => ['nullable', 'array'],
            'party_results.*.party_name' => ['required_with:party_results', 'string', 'max:50'],
            'party_results.*.votes_scored' => ['required_with:party_results', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $valid = (int) $this->input('valid_ballot_papers', 0);
            $invalid = (int) $this->input('invalid_ballot_papers', 0);
            $cast = (int) $this->input('number_cast_votes', 0);

            // Cross-field validation: valid + invalid must not exceed number cast
            if (($valid + $invalid) > $cast) {
                $validator->errors()->add(
                    'valid_ballot_papers',
                    'The sum of valid and invalid ballot papers cannot exceed the number of cast votes.'
                );
            }

            // Warn if counted_voter_papers < number_cast_votes (soft warning via custom field)
            $counted = (int) $this->input('counted_voter_papers', 0);
            if ($counted < $cast && $cast > 0) {
                $validator->errors()->add(
                    'counted_voter_papers_warning',
                    'Warning: counted voter papers is less than number cast. Report flagged for review.'
                );
            }

            // PU agent must submit for their assigned station
            $user = $this->user();
            if ($user && $user->isPollingUnitAgent() && $this->input('polling_station_id')) {
                if ((int) $this->input('polling_station_id') !== $user->polling_station_id) {
                    $validator->errors()->add(
                        'polling_station_id',
                        'You can only submit reports for your assigned polling unit.'
                    );
                }
            }
        });
    }
}
