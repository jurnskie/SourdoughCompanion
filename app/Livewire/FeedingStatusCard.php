<?php

namespace App\Livewire;

use App\Models\Starter;
use App\Services\StarterService;
use Livewire\Component;

class FeedingStatusCard extends Component
{
    public Starter $starter;
    public $showFeedingForm = false;
    public $starterAmount = 10;
    public $flourAmount = 50;
    public $waterAmount = 50;
    public $ratio = '1:5:5';
    
    public function mount(Starter $starter)
    {
        $this->starter = $starter;
    }
    
    public function showFeedingModal()
    {
        $this->showFeedingForm = true;
    }
    
    public function hideFeedingModal()
    {
        $this->showFeedingForm = false;
    }
    
    public function feedStarter()
    {
        $starterService = app(StarterService::class);
        
        try {
            $starterService->addFeeding($this->starter, $this->starterAmount, $this->flourAmount, $this->waterAmount, $this->ratio);
            session()->flash('message', 'Starter fed successfully!');
            $this->hideFeedingModal();
            $this->dispatch('starter-fed');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.feeding-status-card');
    }
}
