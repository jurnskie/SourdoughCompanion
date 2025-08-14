<?php

namespace App\Livewire;

use App\Models\Starter;
use App\Services\StarterService;
use Livewire\Component;

class StarterStatusCard extends Component
{
    public Starter $starter;

    public function mount(Starter $starter)
    {
        $this->starter = $starter;
    }

    public function getHealthStatusProperty()
    {
        return $this->starter->getHealthStatus();
    }

    public function getCanResetProperty()
    {
        try {
            $starterService = app(StarterService::class);
            $resetInfo = $starterService->canResetStarter($this->starter);
            
            // Ensure boolean values are properly cast
            $resetInfo['can_reset'] = (bool) ($resetInfo['can_reset'] ?? false);
            $resetInfo['is_healthy'] = (bool) ($resetInfo['is_healthy'] ?? false);
            $resetInfo['recommended_reset'] = (bool) ($resetInfo['recommended_reset'] ?? false);
            
            return $resetInfo;
        } catch (\Exception $e) {
            \Log::error('StarterStatusCard getCanResetProperty error: ' . $e->getMessage());
            return [
                'can_reset' => false,
                'reason' => 'Error checking reset status',
                'is_healthy' => false,
                'recommended_reset' => false,
                'warning_message' => 'Unable to check starter status'
            ];
        }
    }

    public function testClick()
    {
        session()->flash('message', 'Test click worked! Livewire is functioning.');
    }

    public function resetStarter()
    {
        $starterService = app(StarterService::class);
        
        try {
            $canReset = $starterService->canResetStarter($this->starter);
            
            if (!$canReset['can_reset']) {
                session()->flash('error', $canReset['reason']);
                return;
            }

            // Pass null for reason to let the service auto-determine it based on health
            $newStarter = $starterService->resetStarter(
                $this->starter, 
                null, // Let service determine reason
                true  // User-initiated reset
            );
            
            $message = $canReset['is_healthy'] 
                ? 'Healthy starter has been reset! A fresh starter has been created.'
                : 'Starter has been reset! A fresh starter has been created.';
                
            session()->flash('message', $message);
            
            // Close the modal using Flux's modal system
            $this->modal('reset-starter')->close();
            
            // Redirect to the starter page to see the new starter
            return redirect()->route('starter');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reset starter: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.starter-status-card');
    }
}
