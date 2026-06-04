<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class OperatorMafundi extends Component
{
    use WithPagination;

    public User $operator;

    protected $paginationTheme = 'bootstrap';

    public function mount(User $operator)
    {
        $this->operator = $operator->load(['createdFundis']);

    }

    public function render()
    {

        return view('livewire.operator-mafundi', ['fundis' => $this->operator->createdFundis()->latest('created_at')->paginate(12)]);
    }
}
