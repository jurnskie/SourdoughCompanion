<?php

namespace App\Livewire;

use App\Services\StarterService;
use Livewire\Component;

class FeedingHistory extends Component
{
    public $starter;
    public $feedings = [];
    public $statistics = [];
    
    public function mount()
    {
        $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
        $this->starter = $user ? $user->activeStarter() : null;
        
        if ($this->starter) {
            $this->feedings = $this->starter->feedings()->latest()->get();
            $starterService = app(StarterService::class);
            $this->statistics = $starterService->getFeedingStatistics($this->starter);
        }
    }

    public function render()
    {
        return view('livewire.feeding-history');
    }
}
