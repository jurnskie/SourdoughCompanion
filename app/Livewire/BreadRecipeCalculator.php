<?php

namespace App\Livewire;

use App\Services\BakingTimerService;
use App\Services\NotificationSchedulerService;
use App\Services\StarterService;
use App\Services\WeatherService;
use Livewire\Component;

class BreadRecipeCalculator extends Component
{
    // Input properties
    public $flourWeight = 500;
    public $loaves = 1;
    public $recipeType = 'basic';
    public $useWeather = true;
    public $manualTemperature = 22;
    public $manualHumidity = 'normal';
    public $location = '';
    
    // Calculated results
    public $recipe = null;
    public $weather = null;
    public $isCalculating = false;
    public $activeTimer = null;
    
    protected $rules = [
        'flourWeight' => 'required|integer|min:100|max:2000',
        'loaves' => 'required|integer|min:1|max:10',
        'recipeType' => 'required|in:basic,whole-grain,high-hydration',
        'manualTemperature' => 'required|numeric|min:5|max:40',
        'manualHumidity' => 'required|in:dry,normal,humid',
    ];

    public function mount()
    {
        $this->calculateRecipe();
        $this->checkActiveTimer();
    }

    public function updatedFlourWeight()
    {
        $this->calculateRecipe();
    }

    public function updatedLoaves()
    {
        $this->calculateRecipe();
    }

    public function updatedRecipeType()
    {
        $this->calculateRecipe();
    }

    public function updatedUseWeather()
    {
        $this->calculateRecipe();
    }

    public function updatedManualTemperature()
    {
        if (!$this->useWeather) {
            $this->calculateRecipe();
        }
    }

    public function updatedManualHumidity()
    {
        if (!$this->useWeather) {
            $this->calculateRecipe();
        }
    }

    public function calculateRecipe()
    {
        $this->validate();
        
        $this->isCalculating = true;
        
        try {
            $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
            $starter = $user ? $user->activeStarter() : null;
            
            if (!$starter) {
                $this->recipe = null;
                $this->isCalculating = false;
                return;
            }

            // Get weather data if enabled
            if ($this->useWeather) {
                $weatherService = app(WeatherService::class);
                $this->weather = $weatherService->getCurrentWeather($this->location ?: null);
                $temperature = $this->weather['temperature'];
                $humidityLevel = $weatherService->getHumidityLevel($this->weather['humidity']);
            } else {
                $this->weather = null;
                $temperature = $this->manualTemperature;
                $humidityLevel = $this->manualHumidity;
            }

            // Calculate recipe using StarterService
            $starterService = app(StarterService::class);
            $options = [
                'flour_weight' => $this->flourWeight,
                'loaves' => $this->loaves,
                'temperature' => $temperature,
                'recipe_type' => $this->recipeType,
                'humidity_level' => $humidityLevel,
            ];

            $this->recipe = $starterService->calculateBreadRecipe($starter, $options);
            $this->recipe['humidity_level'] = $humidityLevel;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to calculate recipe: ' . $e->getMessage());
            $this->recipe = null;
        }
        
        $this->isCalculating = false;
    }

    public function fetchWeather()
    {
        $this->useWeather = true;
        $this->calculateRecipe();
    }

    public function startBakingTimer()
    {
        if (!$this->recipe) {
            session()->flash('error', 'Please calculate a recipe first');
            return;
        }

        try {
            $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
            
            if (!$user->telegram_chat_id) {
                session()->flash('error', 'Please set your Telegram Chat ID first to receive notifications');
                return;
            }
            
            $bakingTimerService = app(BakingTimerService::class);
            
            // Convert hours to minutes for the timer
            $recipeForTimer = [
                'bulk_fermentation_time' => (int) ($this->recipe['timing']['bulk_fermentation_hours'] * 60),
                'final_proof_time' => (int) ($this->recipe['timing']['final_proof_hours'] * 60),
                'bake_time' => 45, // Standard bake time in minutes
                'recipe_type' => $this->recipeType,
                'flour_weight' => $this->flourWeight,
                'loaves' => $this->loaves,
            ];
            
            $this->activeTimer = $bakingTimerService->startTimer($user->id, $recipeForTimer);
            
            session()->flash('message', 'Baking timer started! You\'ll receive Telegram notifications at each stage.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start timer: ' . $e->getMessage());
        }
    }

    public function cancelBakingTimer()
    {
        if (!$this->activeTimer) {
            return;
        }

        try {
            $bakingTimerService = app(BakingTimerService::class);
            $bakingTimerService->cancelTimer($this->activeTimer->id);
            
            $this->activeTimer = null;
            session()->flash('message', 'Baking timer cancelled.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to cancel timer: ' . $e->getMessage());
        }
    }

    public function checkActiveTimer()
    {
        try {
            $user = \App\Models\User::where('email', 'sourdough@localhost')->first() ?? \App\Models\User::first();
            if ($user) {
                $bakingTimerService = app(BakingTimerService::class);
                $this->activeTimer = $bakingTimerService->getActiveTimer($user->id);
            }
        } catch (\Exception $e) {
            // Silently fail - timer check is not critical
        }
    }

    public function render()
    {
        return view('livewire.bread-recipe-calculator');
    }
}
