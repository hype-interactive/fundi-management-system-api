<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Fundi;
use Livewire\Component;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Traits\LocationTrait;
use App\Models\SmsCampaignFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsCampaignList extends Component
{
    use WithPagination, LocationTrait;

    protected $paginationTheme = 'bootstrap';

    // Campaign Management
    public $editingCampaign = null;
    public $campaignTitle = '';
    public $campaignMessage = '';
    public $recipientType = 'both'; // all, operators, workers
    public $scheduledAt = null;
    public $sendNow = true;

    // Filters
    public $selectedRegion = null;
    public $selectedDistrict = null;
    public $selectedWard = null;
    public $selectedUsers = [];
    public $selectedFundis = [];

    // Filter options
    public $regions = [];
    public $districts = [];
    public $wards = [];
    public $operators = [];
    public $workers = [];

    // Templates
    public $templates = [];
    public $selectedTemplate = null;

    // Campaign listing
    #[Url]
    public $search = '';

    #[Url]
    public $status = '';

    public $perPage = 10;

    // Recipients preview
    public $recipientCount = 0;
    public $showRecipientsPreview = false;

    protected $rules = [
        'campaignTitle' => 'required|string|max:255',
        'campaignMessage' => 'required|string|max:1000',
        'recipientType' => 'required|in:both,operators,fundis,custom',
        'scheduledAt' => 'nullable|date|after:now',
    ];

    protected $listeners = [
        'campaignCreated' => 'refreshCampaigns',
        'templateSelected' => 'applyTemplate',
        'templateUpdated' => 'refreshTemplates',
    ];

    public function mount()
    {
        $this->loadInitialData();
    }

    public function loadInitialData()
    {
        $this->regions = $this->getAccessibleRegions();
        $this->operators = User::whereHas('role', function ($query) {
            $query->where('name', 'operator');
        })->get();
        $this->workers = Fundi::all();
        $this->templates = SmsTemplate::orderBy('title')->get();
    }

    public function refreshTemplates()
    {
        $this->templates = SmsTemplate::orderBy('title')->get();
    }

    public function resetCampaignForm()
    {
        $this->reset([
            'editingCampaign',
            'campaignTitle',
            'campaignMessage',
            'recipientType',
            'scheduledAt',
            'selectedRegion',
            'selectedDistrict',
            'selectedWard',
            'selectedUsers',
            'selectedFundis',
            'sendNow'
        ]);

        $this->sendNow = true;
        $this->recipientType = 'both';
    }

    public function saveCampaign()
    {
        $this->validate();

        try {
            $campaign = SmsCampaign::create([
                'title' => $this->campaignTitle,
                'message' => $this->campaignMessage,
                'recipient_type' => $this->recipientType,
                'scheduled_at' => $this->sendNow ? null : $this->scheduledAt,
                'status' => $this->sendNow ? 'processing' : 'scheduled',
                'created_by' => Auth::id(),
            ]);

            // Save filters
            $this->saveCampaignFilters($campaign);

            // If sending now, dispatch job
            if ($this->sendNow) {
                $this->dispatchSmsJob($campaign);
            }

            $this->resetCampaignForm();

            session()->flash(
                'success',
                $this->sendNow ?
                    'SMS campaign created and queued for sending!' :
                    'SMS campaign scheduled successfully!'
            );

            $this->dispatch('campaignCreated');
        } catch (\Exception $e) {
            Log::error('Failed to create SMS campaign: ' . $e->getMessage());
            session()->flash('error', 'Failed to create campaign: ' . $e->getMessage());
        }
    }

    private function saveCampaignFilters($campaign)
    {
        // Save location filters
        if ($this->selectedRegion) {
            SmsCampaignFilter::create([
                'sms_campaign_id' => $campaign->id,
                'filter_type' => 'region',
                'filter_value' => $this->selectedRegion,
            ]);
        }

        if ($this->selectedDistrict) {
            SmsCampaignFilter::create([
                'sms_campaign_id' => $campaign->id,
                'filter_type' => 'district',
                'filter_value' => $this->selectedDistrict,
            ]);
        }

        if ($this->selectedWard) {
            SmsCampaignFilter::create([
                'sms_campaign_id' => $campaign->id,
                'filter_type' => 'ward',
                'filter_value' => $this->selectedWard,
            ]);
        }

        // Save specific user/fundi selections
        foreach ($this->selectedUsers as $userId) {
            SmsCampaignFilter::create([
                'sms_campaign_id' => $campaign->id,
                'filter_type' => 'user',
                'filter_value' => $userId,
            ]);
        }

        foreach ($this->selectedFundis as $fundiId) {
            SmsCampaignFilter::create([
                'sms_campaign_id' => $campaign->id,
                'filter_type' => 'fundi',
                'filter_value' => $fundiId,
            ]);
        }
    }

    private function dispatchSmsJob($campaign)
    {
        // Dispatch SMS sending job
        \App\Jobs\SendSmsCampaign::dispatch($campaign);
    }

    public function applyTemplate($templateId)
    {
        $template = SmsTemplate::find($templateId);
        if ($template) {
            $this->campaignMessage = $template->content;
            $this->selectedTemplate = $templateId;
        }
    }

    // Recipient Management
    public function updatedRecipientType()
    {
        $this->calculateRecipientCount();
    }

    public function updatedSelectedRegion()
    {
        $this->districts = $this->getDistricts($this->selectedRegion);
        $this->selectedDistrict = null;
        $this->selectedWard = null;
        $this->wards = collect();
        $this->calculateRecipientCount();
    }

    public function updatedSelectedDistricts()
    {
        $this->wards = $this->getWards($this->selectedDistrict);
        $this->selectedWard = null;
        $this->calculateRecipientCount();
    }

    public function updatedSelectedWards()
    {
        $this->calculateRecipientCount();
    }

    public function calculateRecipientCount()
    {
        $count = 0;

        if ($this->recipientType === 'both' || $this->recipientType === 'operators') {
            $operatorQuery = User::whereHas('role', function ($query) {
                $query->where('name', 'operator');
            });

            if (!empty($this->selectedUsers)) {
                $operatorQuery->whereIn('id', $this->selectedUsers);
            }

            // $this->applyLocationFiltersToQuery(
            //     $operatorQuery,
            //     $this->selectedRegions,
            //     $this->selectedDistricts,
            //     $this->selectedWards
            // );

            $count += $operatorQuery->count();
        }

        if ($this->recipientType === 'both' || $this->recipientType === 'fundis') {
            $fundiQuery = Fundi::query();

            if (!empty($this->selectedFundis)) {
                $fundiQuery->whereIn('id', $this->selectedFundis);
            }

            // $this->applyLocationFiltersToQuery(
            //     $fundiQuery,
            //     $this->selectedRegions,
            //     $this->selectedDistricts,
            //     $this->selectedWards
            // );

            $count += $fundiQuery->count();
        }

        $this->recipientCount = $count;
    }

    // Campaign listing and filtering
    public function getCampaigns()
    {
        return SmsCampaign::with(['creator', 'messages'])
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('message', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function deleteCampaign($campaignId)
    {
        $campaign = SmsCampaign::find($campaignId);
        if ($campaign && $campaign->status !== 'sending') {
            $campaign->delete();
            session()->flash('success', 'Campaign deleted successfully!');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.sms-campaign-list', [
            'campaigns' => $this->getCampaigns(),
        ]);
    }
}
