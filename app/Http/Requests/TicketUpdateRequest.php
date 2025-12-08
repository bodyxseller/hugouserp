<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.edit');
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'in:new,open,pending,resolved,closed'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'category_id' => ['nullable', 'exists:ticket_categories,id'],
            'priority' => ['nullable', 'exists:ticket_priorities,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'sla_policy_id' => ['nullable', 'exists:ticket_sla_policies,id'],
            'due_date' => ['nullable', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'satisfaction_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'satisfaction_comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => __('Ticket subject is required'),
            'description.required' => __('Ticket description is required'),
            'status.in' => __('Invalid ticket status'),
            'customer_id.exists' => __('Selected customer does not exist'),
            'category_id.exists' => __('Selected category does not exist'),
            'priority.exists' => __('Selected priority does not exist'),
            'assigned_to.exists' => __('Selected agent does not exist'),
            'satisfaction_rating.min' => __('Rating must be between 1 and 5'),
            'satisfaction_rating.max' => __('Rating must be between 1 and 5'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set updated_by
        $this->merge([
            'updated_by' => $this->user()->id,
        ]);
    }
}
