<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lawyer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'firm',
        'phone',
        'email',
        'specialization',
        'address',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lawyerTrackings(): HasMany
    {
        return $this->hasMany(LeaseLawyerTracking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByFirm($query, string $firm)
    {
        return $query->where('firm', $firm);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->firm ? "{$this->name} ({$this->firm})" : $this->name;
    }

    public function getPendingLeasesCountAttribute(): int
    {
        return $this->lawyerTrackings()
            ->whereIn('status', ['pending', 'sent'])
            ->count();
    }

    public function getAverageTurnaroundDaysAttribute(): ?float
    {
        $avg = $this->lawyerTrackings()
            ->whereNotNull('turnaround_days')
            ->avg('turnaround_days');

        return $avg ? round($avg, 1) : null;
    }
}
