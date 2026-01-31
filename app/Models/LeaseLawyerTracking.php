<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseLawyerTracking extends Model
{
    use HasFactory;

    protected $table = 'lease_lawyer_tracking';

    protected $fillable = [
        'lease_id',
        'lawyer_id',
        'sent_method',
        'sent_at',
        'sent_by',
        'sent_notes',
        'returned_method',
        'returned_at',
        'received_by',
        'returned_notes',
        'turnaround_days',
        'status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeWithLawyer($query)
    {
        return $query->whereIn('status', ['pending', 'sent']);
    }

    public function markAsSent(string $method, int $userId, ?string $notes = null): void
    {
        $this->update([
            'sent_method' => $method,
            'sent_at' => now(),
            'sent_by' => $userId,
            'sent_notes' => $notes,
            'status' => 'sent',
        ]);
    }

    public function markAsReturned(string $method, int $userId, ?string $notes = null): void
    {
        $sentAt = $this->sent_at;
        $returnedAt = now();
        $turnaroundDays = $sentAt ? $sentAt->diffInDays($returnedAt) : null;

        $this->update([
            'returned_method' => $method,
            'returned_at' => $returnedAt,
            'received_by' => $userId,
            'returned_notes' => $notes,
            'turnaround_days' => $turnaroundDays,
            'status' => 'returned',
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isWithLawyer(): bool
    {
        return in_array($this->status, ['pending', 'sent']);
    }

    public function isOverdue(int $expectedDays = 7): bool
    {
        if ($this->status !== 'sent' || ! $this->sent_at) {
            return false;
        }

        return $this->sent_at->addDays($expectedDays)->isPast();
    }
}
