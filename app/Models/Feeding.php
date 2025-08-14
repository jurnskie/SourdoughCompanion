<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feeding extends Model
{
    protected $fillable = [
        'starter_id',
        'day',
        'starter_amount',
        'flour_amount',
        'water_amount',
        'ratio',
        'notes',
    ];

    protected $casts = [
        'notes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function starter(): BelongsTo
    {
        return $this->belongsTo(Starter::class);
    }

    public function getTotalAmountAttribute(): int
    {
        return $this->starter_amount + $this->flour_amount + $this->water_amount;
    }

    public function getHydrationPercentageAttribute(): float
    {
        if ($this->flour_amount === 0) {
            return 0;
        }
        
        return round(($this->water_amount / $this->flour_amount) * 100, 1);
    }
}
