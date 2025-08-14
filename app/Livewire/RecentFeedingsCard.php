<?php

namespace App\Livewire;

use App\Models\Starter;
use Livewire\Component;

class RecentFeedingsCard extends Component
{
    public Starter $starter;

    public function mount(Starter $starter)
    {
        $this->starter = $starter;
    }

    public function render()
    {
        return view('livewire.recent-feedings-card');
    }
}
