<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;


class OperatorMafundi extends Component
{

    use WithPagination;
    public User $operator;
    protected $paginationTheme = 'bootstrap';

    function mount(User $operator) {
        $this->operator = $operator->load(['createdFundis']);

    }

    public function render()
    {
        
        return view('livewire.operator-mafundi', ['fundis'=> $this->operator->createdFundis()->latest('created_at')->paginate(12)]);
    }
}
