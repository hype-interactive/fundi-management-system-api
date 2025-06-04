<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SmsTemplate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class SmsTemplateList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';


    public $editingTemplate = null;
    public $templateTitle = '';
    public $templateContent = '';

    // Template listing
    #[Url]
    public $search = '';

    public $perPage = 10;

    protected $rules = [
        'templateTitle' => 'required|string|max:255',
        'templateContent' => 'required|string|max:1000',
    ];

    protected $listeners = [
        'templateCreated' => 'refreshTemplates',
    ];

    public function resetTemplateForm()
    {
        $this->reset([
            'editingTemplate',
            'templateTitle',
            'templateContent'
        ]);
    }

    public function editTemplate($templateId)
    {
        $template = SmsTemplate::find($templateId);
        if ($template) {
            $this->editingTemplate = $template->id;
            $this->templateTitle = $template->title;
            $this->templateContent = $template->content;
        }
    }

    public function saveTemplate()
    {
        $this->validate();

        try {
            if ($this->editingTemplate) {
                // Update existing template
                $template = SmsTemplate::find($this->editingTemplate);
                $template->update([
                    'title' => $this->templateTitle,
                    'content' => $this->templateContent,
                ]);

                session()->flash('success', 'Template updated successfully!');
            } else {
                // Create new template
                SmsTemplate::create([
                    'title' => $this->templateTitle,
                    'content' => $this->templateContent,
                    'created_by' => Auth::id(),
                ]);

                session()->flash('success', 'Template created successfully!');
            }

            $this->resetTemplateForm();

            // Notify other components that templates have been updated
            $this->dispatch('templateUpdated');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save template: ' . $e->getMessage());
        }
    }

    public function deleteTemplate($templateId)
    {
        try {
            $template = SmsTemplate::find($templateId);
            if ($template) {
                $template->delete();
                session()->flash('success', 'Template deleted successfully!');

                // Notify other components that templates have been updated
                $this->dispatch('templateUpdated');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    public function selectTemplate($templateId)
    {
        // Dispatch event to notify campaign composer
        $this->dispatch('templateSelected', templateId: $templateId);
    }

    public function duplicateTemplate($templateId)
    {
        try {
            $template = SmsTemplate::find($templateId);
            if ($template) {
                SmsTemplate::create([
                    'title' => $template->title . ' (Copy)',
                    'content' => $template->content,
                    'created_by' => Auth::id(),
                ]);

                session()->flash('success', 'Template duplicated successfully!');
                $this->dispatch('templateUpdated');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to duplicate template: ' . $e->getMessage());
        }
    }

    // Template listing and filtering
    public function getTemplates()
    {
        return SmsTemplate::with('creator')
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('content', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function refresh()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.sms-template-list', [
            'templates' => $this->getTemplates(),
        ]);
    }
}
