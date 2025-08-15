<?php

namespace App\Livewire;

use App\Models\Starter;
use App\Services\StarterService;
use Livewire\Component;
use Livewire\WithFileUploads;

class FeedingStatusCard extends Component
{
    use WithFileUploads;
    public Starter $starter;
    public $showFeedingForm = false;
    public $starterAmount = 10;
    public $flourAmount = 50;
    public $waterAmount = 50;
    public $ratio = '1:5:5';
    public $photo;
    
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
        $this->photo = null; // Reset photo when closing modal
    }
    
    public function feedStarter()
    {
        $starterService = app(StarterService::class);
        
        try {
            $starterService->addFeeding(
                $this->starter, 
                $this->starterAmount, 
                $this->flourAmount, 
                $this->waterAmount, 
                $this->ratio,
                $this->photo
            );
            session()->flash('message', 'Starter fed successfully!');
            $this->hideFeedingModal();
            $this->dispatch('starter-fed');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function removePhoto()
    {
        $this->photo = null;
    }

    public function render()
    {
        return view('livewire.feeding-status-card');
    }
}
