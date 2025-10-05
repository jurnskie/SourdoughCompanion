<?php

namespace App\Livewire;

use App\Services\StarterService;
use Livewire\Component;

class CreateStarterForm extends Component
{
    public $name = 'My Sourdough Starter';

    public $flour_type = 'whole wheat';

    public $showForm = false;

    protected $rules = [
        'name' => 'required|max:255',
        'flour_type' => 'required|max:100',
    ];

    public function showCreateForm()
    {
        $this->showForm = true;
    }

    public function hideCreateForm()
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function createStarter()
    {
        $this->validate();

        $starterService = app(StarterService::class);
        $starter = $starterService->createStarter($this->name, $this->flour_type);

        // Emit event to refresh starters list
        $this->dispatch('starter-created');

        session()->flash('message', 'Starter created successfully!');

        return redirect()->route('starter');
    }

    public function render()
    {
        return view('livewire.create-starter-form');
    }
}
