<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BakingTimer extends Model
{
    protected $fillable = [
        'user_id',
        'recipe_data',
        'start_time',
        'total_duration_minutes',
        'current_stage',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'recipe_data' => 'array',
        'start_time' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getElapsedMinutes(): int
    {
        return (int) $this->start_time->diffInMinutes(now());
    }

    public function getRemainingMinutes(): int
    {
        $elapsed = $this->getElapsedMinutes();
        return (int) max(0, $this->total_duration_minutes - $elapsed);
    }

    public function getProgress(): int
    {
        if ($this->total_duration_minutes <= 0) {
            return 100;
        }

        $elapsed = $this->getElapsedMinutes();
        return min(100, (int) round(($elapsed / $this->total_duration_minutes) * 100));
    }

    public function getCurrentStageInfo(): array
    {
        $recipe = $this->recipe_data;
        $elapsed = $this->getElapsedMinutes();
        
        $bulkTime = $recipe['bulk_fermentation_time'] ?? 0;
        $proofTime = $recipe['final_proof_time'] ?? 0;
        $bakeTime = $recipe['bake_time'] ?? 45;

        if ($elapsed < $bulkTime) {
            return [
                'stage' => 'bulk_fermentation',
                'name' => 'Bulk Fermentation',
                'remaining' => (int) ($bulkTime - $elapsed),
                'total' => (int) $bulkTime,
            ];
        } elseif ($elapsed < $bulkTime + $proofTime) {
            return [
                'stage' => 'final_proof',
                'name' => 'Final Proof', 
                'remaining' => (int) (($bulkTime + $proofTime) - $elapsed),
                'total' => (int) $proofTime,
            ];
        } else {
            return [
                'stage' => 'baking',
                'name' => 'Baking',
                'remaining' => (int) max(0, ($bulkTime + $proofTime + $bakeTime) - $elapsed),
                'total' => (int) $bakeTime,
            ];
        }
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
