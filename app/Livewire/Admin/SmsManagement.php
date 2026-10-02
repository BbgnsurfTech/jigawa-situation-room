<?php

namespace App\Livewire\Admin;

use App\Models\Lga;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Ward;
use App\Services\SmsService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class SmsManagement extends Component
{
    use WithPagination;

    public string $filterDirection = '';
    public string $filterStatus = '';

    // Active tab: 'history', 'templates', 'bulk'
    public string $activeTab = 'history';

    // Compose single SMS
    public bool $showCompose = false;
    public string $toPhone = '';
    public string $message = '';
    public string $sendError = '';
    public bool $sendSuccess = false;

    // User search for recipient
    public string $userSearch = '';
    public ?int $selectedUserId = null;
    public bool $manualEntry = false;
    public bool $showUserDropdown = false;

    // SMS Templates
    public ?int $selectedTemplateId = null;

    // Template CRUD
    public bool $showTemplateModal = false;
    public ?int $editingTemplateId = null;
    public string $templateName = '';
    public string $templateSlug = '';
    public string $templateMessage = '';
    public string $templateCategory = 'general';
    public string $templateDescription = '';
    public string $templatePlaceholders = '';
    public bool $templateIsActive = true;

    // Bulk SMS
    public string $bulkFilterRole = '';
    public ?int $bulkFilterLgaId = null;
    public ?int $bulkFilterWardId = null;
    public string $bulkMessage = '';
    public ?int $bulkTemplateId = null;
    public array $bulkSelectedUsers = [];
    public bool $bulkSending = false;
    public int $bulkSentCount = 0;
    public int $bulkFailedCount = 0;
    public string $bulkError = '';
    public bool $bulkSuccess = false;

    // Delete confirmation
    public bool $showDeleteConfirm = false;
    public ?int $deleteTemplateId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ==========================================
    // COMPOSE SINGLE SMS
    // ==========================================

    public function openCompose(): void
    {
        $this->showCompose = true;
        $this->resetComposeForm();
    }

    public function closeCompose(): void
    {
        $this->showCompose = false;
        $this->resetComposeForm();
    }

    protected function resetComposeForm(): void
    {
        $this->toPhone = '';
        $this->message = '';
        $this->sendError = '';
        $this->sendSuccess = false;
        $this->userSearch = '';
        $this->selectedUserId = null;
        $this->manualEntry = false;
        $this->showUserDropdown = false;
        $this->selectedTemplateId = null;
    }

    /**
     * Search users by name, phone, role, or location.
     */
    #[Computed]
    public function searchResults(): Collection
    {
        if (strlen($this->userSearch) < 2) {
            return collect();
        }

        return $this->searchUsers($this->userSearch);
    }

    /**
     * Search users with given query.
     */
    protected function searchUsers(string $query): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('full_name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%")
                    ->orWhereHas('lga', fn($lq) => $lq->where('name', 'like', "%{$query}%"))
                    ->orWhereHas('ward', fn($wq) => $wq->where('name', 'like', "%{$query}%"));
            })
            ->with(['lga', 'ward'])
            ->orderByRaw("CASE role
                WHEN 'super_admin' THEN 1
                WHEN 'state_admin' THEN 2
                WHEN 'supervisor' THEN 3
                WHEN 'ward_agent' THEN 4
                WHEN 'polling_unit_agent' THEN 5
                ELSE 6 END")
            ->orderBy('full_name')
            ->limit(15)
            ->get();
    }

    public function selectUser(int $userId): void
    {
        $user = User::find($userId);
        if ($user && $user->phone) {
            $this->selectedUserId = $userId;
            $this->toPhone = $user->phone;
            $this->userSearch = $user->full_name ?? $user->name;
            $this->showUserDropdown = false;
        }
    }

    public function clearSelectedUser(): void
    {
        $this->selectedUserId = null;
        $this->toPhone = '';
        $this->userSearch = '';
        $this->showUserDropdown = false;
    }

    public function toggleManualEntry(): void
    {
        $this->manualEntry = !$this->manualEntry;
        if ($this->manualEntry) {
            $this->selectedUserId = null;
            $this->userSearch = '';
        } else {
            $this->toPhone = '';
        }
    }

    public function updatedUserSearch(): void
    {
        $this->showUserDropdown = strlen($this->userSearch) >= 2;
        if ($this->selectedUserId) {
            $user = User::find($this->selectedUserId);
            if ($user && $this->userSearch !== ($user->full_name ?? $user->name)) {
                $this->selectedUserId = null;
                $this->toPhone = '';
            }
        }
    }

    /**
     * Apply template to compose message.
     */
    public function applyTemplate(int $templateId): void
    {
        $template = SmsTemplate::find($templateId);
        if ($template) {
            $this->message = $template->message;
            $this->selectedTemplateId = $templateId;
        }
    }

    public function clearTemplate(): void
    {
        $this->selectedTemplateId = null;
    }

    public function send(SmsService $sms): void
    {
        $this->validate([
            'toPhone' => 'required|string|regex:/^0[789][01]\d{8}$/',
            'message' => 'required|string|min:5|max:320',
        ], [
            'toPhone.regex' => 'Phone must be a valid Nigerian mobile number (e.g. 08012345678).',
            'message.max' => 'Message cannot exceed 320 characters (2 SMS parts).',
        ]);

        $sent = $sms->send($this->toPhone, $this->message);

        if ($sent) {
            $this->sendSuccess = true;
            $this->toPhone = '';
            $this->message = '';
            $this->selectedUserId = null;
            $this->userSearch = '';
            $this->selectedTemplateId = null;
        } else {
            $this->sendError = 'Failed to send. Check SMS service configuration.';
        }
    }

    // ==========================================
    // TEMPLATE CRUD
    // ==========================================

    #[Computed]
    public function templates(): Collection
    {
        return SmsTemplate::orderBy('category')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function activeTemplates(): Collection
    {
        return SmsTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function templateCategories(): array
    {
        return SmsTemplate::CATEGORIES;
    }

    public function openTemplateModal(?int $templateId = null): void
    {
        $this->resetTemplateForm();
        $this->showTemplateModal = true;

        if ($templateId) {
            $template = SmsTemplate::find($templateId);
            if ($template && !$template->is_system) {
                $this->editingTemplateId = $templateId;
                $this->templateName = $template->name;
                $this->templateSlug = $template->slug;
                $this->templateMessage = $template->message;
                $this->templateCategory = $template->category;
                $this->templateDescription = $template->description ?? '';
                $this->templatePlaceholders = $template->placeholders ? implode(', ', $template->placeholders) : '';
                $this->templateIsActive = $template->is_active;
            }
        }
    }

    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
        $this->resetTemplateForm();
    }

    protected function resetTemplateForm(): void
    {
        $this->editingTemplateId = null;
        $this->templateName = '';
        $this->templateSlug = '';
        $this->templateMessage = '';
        $this->templateCategory = 'general';
        $this->templateDescription = '';
        $this->templatePlaceholders = '';
        $this->templateIsActive = true;
    }

    public function saveTemplate(): void
    {
        $rules = [
            'templateName' => 'required|string|min:3|max:100',
            'templateMessage' => 'required|string|min:5|max:320',
            'templateCategory' => 'required|in:' . implode(',', array_keys(SmsTemplate::CATEGORIES)),
            'templateDescription' => 'nullable|string|max:500',
            'templatePlaceholders' => 'nullable|string|max:200',
        ];

        // Slug uniqueness check
        if ($this->editingTemplateId) {
            $rules['templateSlug'] = 'nullable|string|max:100|unique:sms_templates,slug,' . $this->editingTemplateId;
        } else {
            $rules['templateSlug'] = 'nullable|string|max:100|unique:sms_templates,slug';
        }

        $this->validate($rules);

        $placeholders = $this->templatePlaceholders
            ? array_map('trim', explode(',', $this->templatePlaceholders))
            : null;

        $data = [
            'name' => $this->templateName,
            'message' => $this->templateMessage,
            'category' => $this->templateCategory,
            'description' => $this->templateDescription ?: null,
            'placeholders' => $placeholders,
            'is_active' => $this->templateIsActive,
        ];

        if ($this->templateSlug) {
            $data['slug'] = $this->templateSlug;
        }

        if ($this->editingTemplateId) {
            $template = SmsTemplate::find($this->editingTemplateId);
            if ($template && !$template->is_system) {
                $template->update($data);
            }
        } else {
            $data['created_by'] = auth()->id();
            $data['is_system'] = false;
            SmsTemplate::create($data);
        }

        $this->closeTemplateModal();
        unset($this->templates, $this->activeTemplates);
    }

    public function confirmDeleteTemplate(int $templateId): void
    {
        $template = SmsTemplate::find($templateId);
        if ($template && !$template->is_system) {
            $this->deleteTemplateId = $templateId;
            $this->showDeleteConfirm = true;
        }
    }

    public function deleteTemplate(): void
    {
        if ($this->deleteTemplateId) {
            $template = SmsTemplate::find($this->deleteTemplateId);
            if ($template && !$template->is_system) {
                $template->delete();
                unset($this->templates, $this->activeTemplates);
            }
        }
        $this->showDeleteConfirm = false;
        $this->deleteTemplateId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTemplateId = null;
    }

    public function toggleTemplateActive(int $templateId): void
    {
        $template = SmsTemplate::find($templateId);
        if ($template) {
            $template->update(['is_active' => !$template->is_active]);
            unset($this->templates, $this->activeTemplates);
        }
    }

    // ==========================================
    // BULK SMS
    // ==========================================

    #[Computed]
    public function lgas(): Collection
    {
        $query = Lga::orderBy('name');

        // Scope to deployment state if configured
        $deploymentScope = config('jesr.deployment_scope', 'state');
        $stateCode = config('jesr.state_code', 'JIG');

        if ($deploymentScope === 'state' && $stateCode) {
            $query->whereHas('state', fn($q) => $q->where('code', $stateCode));
        }

        return $query->get();
    }

    #[Computed]
    public function wards(): Collection
    {
        if (!$this->bulkFilterLgaId) {
            return collect();
        }

        return Ward::where('lga_id', $this->bulkFilterLgaId)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function bulkRecipients(): Collection
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereNotNull('phone');

        if ($this->bulkFilterRole) {
            $query->where('role', $this->bulkFilterRole);
        }

        if ($this->bulkFilterLgaId) {
            $query->where('lga_id', $this->bulkFilterLgaId);
        }

        if ($this->bulkFilterWardId) {
            $query->where('ward_id', $this->bulkFilterWardId);
        }

        // Scope by deployment
        $deploymentScope = config('jesr.deployment_scope', 'state');
        $stateCode = config('jesr.state_code', 'JIG');

        if ($deploymentScope === 'state' && $stateCode) {
            $query->whereHas('lga.state', fn($q) => $q->where('code', $stateCode));
        }

        return $query->with(['lga', 'ward'])
            ->orderBy('role')
            ->orderBy('full_name')
            ->get();
    }

    public function updatedBulkFilterLgaId(): void
    {
        $this->bulkFilterWardId = null;
        unset($this->wards, $this->bulkRecipients);
    }

    public function updatedBulkFilterRole(): void
    {
        unset($this->bulkRecipients);
    }

    public function updatedBulkFilterWardId(): void
    {
        unset($this->bulkRecipients);
    }

    public function applyBulkTemplate(int $templateId): void
    {
        $template = SmsTemplate::find($templateId);
        if ($template) {
            $this->bulkMessage = $template->message;
            $this->bulkTemplateId = $templateId;
        }
    }

    public function clearBulkTemplate(): void
    {
        $this->bulkTemplateId = null;
    }

    public function selectAllRecipients(): void
    {
        $this->bulkSelectedUsers = $this->bulkRecipients->pluck('id')->toArray();
    }

    public function clearAllRecipients(): void
    {
        $this->bulkSelectedUsers = [];
    }

    public function sendBulkSms(SmsService $sms): void
    {
        $this->validate([
            'bulkMessage' => 'required|string|min:5|max:320',
            'bulkSelectedUsers' => 'required|array|min:1',
        ], [
            'bulkSelectedUsers.required' => 'Please select at least one recipient.',
            'bulkSelectedUsers.min' => 'Please select at least one recipient.',
        ]);

        $this->bulkSending = true;
        $this->bulkSentCount = 0;
        $this->bulkFailedCount = 0;
        $this->bulkError = '';
        $this->bulkSuccess = false;

        $users = User::whereIn('id', $this->bulkSelectedUsers)
            ->whereNotNull('phone')
            ->get();

        foreach ($users as $user) {
            // Render message with user placeholders
            $message = $this->renderMessageForUser($this->bulkMessage, $user);
            $sent = $sms->send($user->phone, $message);

            if ($sent) {
                $this->bulkSentCount++;
            } else {
                $this->bulkFailedCount++;
            }
        }

        $this->bulkSending = false;

        if ($this->bulkFailedCount === 0) {
            $this->bulkSuccess = true;
            $this->bulkMessage = '';
            $this->bulkSelectedUsers = [];
            $this->bulkTemplateId = null;
        } else {
            $this->bulkError = "{$this->bulkSentCount} sent, {$this->bulkFailedCount} failed.";
        }
    }

    /**
     * Replace common placeholders in message with user data.
     */
    protected function renderMessageForUser(string $message, User $user): string
    {
        $replacements = [
            '{name}' => $user->name,
            '{NAME}' => $user->name,
            '{full_name}' => $user->full_name ?? $user->name,
            '{FULL_NAME}' => $user->full_name ?? $user->name,
            '{role}' => ucfirst(str_replace('_', ' ', $user->role)),
            '{ROLE}' => strtoupper(str_replace('_', ' ', $user->role)),
            '{phone}' => $user->phone,
            '{PHONE}' => $user->phone,
            '{lga}' => $user->lga?->name ?? '',
            '{LGA}' => $user->lga?->name ?? '',
            '{ward}' => $user->ward?->name ?? '',
            '{WARD}' => $user->ward?->name ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    public function resetBulkForm(): void
    {
        $this->bulkFilterRole = '';
        $this->bulkFilterLgaId = null;
        $this->bulkFilterWardId = null;
        $this->bulkMessage = '';
        $this->bulkTemplateId = null;
        $this->bulkSelectedUsers = [];
        $this->bulkSending = false;
        $this->bulkSentCount = 0;
        $this->bulkFailedCount = 0;
        $this->bulkError = '';
        $this->bulkSuccess = false;
        unset($this->wards, $this->bulkRecipients);
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render()
    {
        $query = SmsMessage::orderByDesc('created_at');
        if ($this->filterDirection) $query->where('direction', $this->filterDirection);
        if ($this->filterStatus) $query->where('delivery_status', $this->filterStatus);

        $stats = [
            'total' => SmsMessage::count(),
            'inbound' => SmsMessage::where('direction', 'inbound')->count(),
            'outbound' => SmsMessage::where('direction', 'outbound')->count(),
            'delivered' => SmsMessage::where('delivery_status', 'delivered')->count(),
            'failed' => SmsMessage::where('delivery_status', 'failed')->count(),
        ];

        return view('livewire.admin.sms-management', [
            'messages' => $query->paginate(25),
            'stats' => $stats,
        ])->layout('layouts.app', ['title' => 'SMS Management']);
    }
}
