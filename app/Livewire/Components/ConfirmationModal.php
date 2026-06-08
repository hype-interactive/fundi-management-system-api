<?php

namespace App\Livewire\Components;

use Livewire\Component;

class ConfirmationModal extends Component
{
    public $show = false;

    public $title = '';

    public $message = '';

    public $type = 'delete'; // delete, approve, reject

    public $itemName = '';

    public $itemDetails = [];

    public $rejectionReason = '';

    public $confirmMethod = '';

    public $confirmParams = [];

    protected $listeners = ['showConfirmation'];

    public function showConfirmation($data)
    {
        $this->title = $data['title'] ?? 'Confirm Action';
        $this->message = $data['message'] ?? 'Are you sure you want to proceed?';
        $this->type = $data['type'] ?? 'delete';
        $this->itemName = $data['itemName'] ?? '';
        $this->itemDetails = $data['itemDetails'] ?? [];
        $this->confirmMethod = $data['confirmMethod'] ?? '';
        $this->confirmParams = $data['confirmParams'] ?? [];
        $this->rejectionReason = '';
        $this->show = true;
    }

    public function confirm()
    {
        if ($this->type === 'reject' && empty($this->rejectionReason)) {
            $this->addError('rejectionReason', 'Rejection reason is required.');

            return;
        }

        $params = $this->confirmParams;
        if ($this->type === 'reject') {
            $params['reason'] = $this->rejectionReason;
        }

        $this->dispatch($this->confirmMethod, $params);
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->show = false;
        $this->reset(['title', 'message', 'type', 'itemName', 'itemDetails', 'rejectionReason', 'confirmMethod', 'confirmParams']);
    }

    public function render()
    {
        return view('livewire.components.confirmation-modal');
    }
}
