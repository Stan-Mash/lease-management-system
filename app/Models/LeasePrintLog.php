<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeasePrintLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'user_id',
        'printed_at',
        'workstation',
        'ip_address',
        'copies_printed',
        'print_reason',
        'notes',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'copies_printed' => 'integer',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logPrint(
        int $leaseId,
        int $userId,
        int $copies = 1,
        ?string $workstation = null,
        ?string $ipAddress = null,
        ?string $reason = null,
        ?string $notes = null
    ): self {
        return self::create([
            'lease_id' => $leaseId,
            'user_id' => $userId,
            'printed_at' => now(),
            'workstation' => $workstation ?? gethostname(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'copies_printed' => $copies,
            'print_reason' => $reason,
            'notes' => $notes,
        ]);
    }

    public function scopeForLease($query, int $leaseId)
    {
        return $query->where('lease_id', $leaseId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('printed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('printed_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('printed_at', now()->month)
            ->whereYear('printed_at', now()->year);
    }
}
