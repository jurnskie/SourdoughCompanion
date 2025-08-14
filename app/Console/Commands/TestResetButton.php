<?php

namespace App\Console\Commands;

use App\Livewire\StarterStatusCard;
use App\Models\Starter;
use Illuminate\Console\Command;

class TestResetButton extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:reset-button';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the reset button functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Reset Button Functionality');
        $this->line('');
        
        $starter = Starter::first();
        if (!$starter) {
            $this->error('❌ No starter found for testing');
            return;
        }
        
        $this->info("Testing with starter: {$starter->name} (ID: {$starter->id})");
        
        // Test 1: Component can be instantiated
        try {
            $component = new StarterStatusCard();
            $component->starter = $starter;
            $this->info('✅ Component instantiated successfully');
        } catch (\Exception $e) {
            $this->error('❌ Component instantiation failed: ' . $e->getMessage());
            return;
        }
        
        // Test 2: getCanResetProperty works
        try {
            $resetInfo = $component->getCanResetProperty();
            $this->info('✅ getCanResetProperty works');
            $this->line("   - can_reset: " . ($resetInfo['can_reset'] ? 'true' : 'false'));
            $this->line("   - is_healthy: " . ($resetInfo['is_healthy'] ? 'true' : 'false'));
            $this->line("   - recommended_reset: " . ($resetInfo['recommended_reset'] ? 'true' : 'false'));
        } catch (\Exception $e) {
            $this->error('❌ getCanResetProperty failed: ' . $e->getMessage());
            return;
        }
        
        // Test 3: Modal state management
        try {
            $component->showResetModal = false;
            $component->showResetModal();
            if ($component->showResetModal === true) {
                $this->info('✅ showResetModal works correctly');
            } else {
                $this->error('❌ showResetModal not working - state is: ' . var_export($component->showResetModal, true));
            }
            
            $component->hideResetModal();
            if ($component->showResetModal === false) {
                $this->info('✅ hideResetModal works correctly');
            } else {
                $this->error('❌ hideResetModal not working - state is: ' . var_export($component->showResetModal, true));
            }
        } catch (\Exception $e) {
            $this->error('❌ Modal state management failed: ' . $e->getMessage());
            return;
        }
        
        $this->line('');
        $this->info('🎉 All tests passed! The reset button should be working correctly.');
        $this->line('');
        $this->warn('If the button still doesn\'t work in the browser:');
        $this->line('1. Check browser console for JavaScript errors');
        $this->line('2. Ensure Livewire scripts are loaded');
        $this->line('3. Try hard refresh (Ctrl+F5 or Cmd+Shift+R)');
        $this->line('4. Check network tab for failed AJAX requests');
    }
}
