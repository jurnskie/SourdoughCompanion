<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Starter extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'flour_type',
        'notes',
    ];

    protected $casts = [
        'notes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedings(): HasMany
    {
        return $this->hasMany(Feeding::class);
    }

    public function getCurrentDay(): int
    {
        return (int) ($this->created_at->diffInDays(now()) + 1);
    }

    public function getCurrentPhase(): string
    {
        $day = $this->getCurrentDay();

        if ($day <= 7) {
            return 'creation';
        }

        return 'maintenance';
    }

    public function getNextFeedingTime(): ?Carbon
    {
        $lastFeeding = $this->feedings()->latest()->first();
        
        if (!$lastFeeding) {
            return null;
        }

        return $lastFeeding->created_at->addHours(24);
    }

    public function canFeedNow(): array
    {
        $lastFeeding = $this->feedings()->latest()->first();
        
        if (!$lastFeeding) {
            return ['can_feed' => true, 'reason' => null, 'next_feeding_time' => null];
        }

        $phase = $this->getCurrentPhase();
        $minimumHours = $this->getMinimumFeedingInterval($phase);
        $nextFeedingTime = $lastFeeding->created_at->copy()->addHours($minimumHours);
        
        if (now()->lt($nextFeedingTime)) {
            return [
                'can_feed' => false,
                'reason' => "Too soon to feed. Last fed {$lastFeeding->created_at->diffForHumans()}. Wait until {$nextFeedingTime->format('M j, g:i A')}",
                'next_feeding_time' => $nextFeedingTime,
                'hours_remaining' => now()->diffInHours($nextFeedingTime, false)
            ];
        }

        return ['can_feed' => true, 'reason' => null, 'next_feeding_time' => null];
    }

    public function getMinimumFeedingInterval(string $phase): int
    {
        return match ($phase) {
            'creation' => 20,
            'maintenance' => 12,
            default => 24
        };
    }

    public function getRecommendedRatio(): string
    {
        $phase = $this->getCurrentPhase();
        $day = $this->getCurrentDay();

        if ($phase === 'creation') {
            if ($day <= 2) {
                return '1:1:1';
            } else {
                return '1:5:5';
            }
        }

        return '1:5:5';
    }

    public function isReadyForNextPhase(): bool
    {
        $phase = $this->getCurrentPhase();

        if ($phase === 'creation') {
            return $this->getCurrentDay() >= 7 && $this->feedings()->count() >= 5;
        }

        return true;
    }

    public function getHealthStatus(): array
    {
        $lastFeeding = $this->feedings()->latest()->first();
        
        if (!$lastFeeding) {
            return [
                'status' => 'unknown',
                'message' => 'No feedings yet',
                'color' => 'gray',
                'icon' => 'question-mark-circle',
                'days_since_feeding' => null
            ];
        }
        
        $daysSinceFeeding = now()->diffInDays($lastFeeding->created_at);
        
        if ($daysSinceFeeding == 0) {
            return [
                'status' => 'excellent',
                'message' => 'Excellent',
                'color' => 'green',
                'icon' => 'check-circle',
                'days_since_feeding' => $daysSinceFeeding
            ];
        } elseif ($daysSinceFeeding <= 2) {
            return [
                'status' => 'good',
                'message' => 'Good',
                'color' => 'green',
                'icon' => 'check-circle',
                'days_since_feeding' => $daysSinceFeeding
            ];
        } elseif ($daysSinceFeeding <= 5) {
            return [
                'status' => 'fair',
                'message' => 'Needs attention',
                'color' => 'orange',
                'icon' => 'exclamation-triangle',
                'days_since_feeding' => $daysSinceFeeding
            ];
        } else {
            return [
                'status' => 'poor',
                'message' => 'Neglected',
                'color' => 'red',
                'icon' => 'x-circle',
                'days_since_feeding' => $daysSinceFeeding
            ];
        }
    }
}
