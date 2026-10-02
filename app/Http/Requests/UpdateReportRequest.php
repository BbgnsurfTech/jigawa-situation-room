<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'counted_voter_papers' => ['sometimes', 'integer', 'min:0'],
            'security_present' => ['sometimes', 'boolean'],
            'security_types' => ['sometimes', 'nullable', 'array'],
            'security_types.*' => [Rule::in(['POLICE', 'CIVIL_DEFENCE', 'NSCDC', 'ARMY', 'VIGILANTE', 'OTHER'])],
            'voting_closed_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'number_cast_votes' => ['sometimes', 'integer', 'min:0'],
            'voting_papers_left' => ['sometimes', 'integer', 'min:0'],
            'valid_ballot_papers' => ['sometimes', 'integer', 'min:0'],
            'invalid_ballot_papers' => ['sometimes', 'integer', 'min:0'],
            'remarks' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'correction_reason' => ['required', 'string', 'min:10'],
        ];
    }
}
