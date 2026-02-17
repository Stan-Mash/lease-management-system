<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Event types for the Tenant 360 CRM Timeline.
 * Color-coded for quick visual identification in the activity feed.
 */
enum TenantEventType: string implements HasColor, HasIcon, HasLabel
{
    case SMS = 'sms';
    case EMAIL = 'email';
    case NOTE = 'note';
    case SYSTEM = 'system';
    case FINANCIAL = 'financial';
    case DISPUTE = 'dispute';
    case CALL = 'call';
    case VISIT = 'visit';
    case DOCUMENT = 'document';
    case LEASE = 'lease';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SMS => 'SMS Message',
            self::EMAIL => 'Email',
            self::NOTE => 'Internal Note',
            self::SYSTEM => 'System Event',
            self::FINANCIAL => 'Financial Transaction',
            self::DISPUTE => 'Dispute/Complaint',
            self::CALL => 'Phone Call',
            self::VISIT => 'Site Visit',
            self::DOCUMENT => 'Document',
            self::LEASE => 'Lease Activity',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SMS => 'info',           // Blue
            self::EMAIL => 'primary',      // Primary blue
            self::NOTE => 'gray',          // Gray/neutral
            self::SYSTEM => 'warning',     // Yellow/orange
            self::FINANCIAL => 'success',  // Green
            self::DISPUTE => 'danger',     // Red
            self::CALL => 'info',          // Blue
            self::VISIT => 'success',      // Green
            self::DOCUMENT => 'gray',      // Gray
            self::LEASE => 'primary',      // Primary blue
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SMS => 'heroicon-o-chat-bubble-left-right',
            self::EMAIL => 'heroicon-o-envelope',
            self::NOTE => 'heroicon-o-pencil-square',
            self::SYSTEM => 'heroicon-o-cog-6-tooth',
            self::FINANCIAL => 'heroicon-o-currency-dollar',
            self::DISPUTE => 'heroicon-o-exclamation-triangle',
            self::CALL => 'heroicon-o-phone',
            self::VISIT => 'heroicon-o-map-pin',
            self::DOCUMENT => 'heroicon-o-document-text',
            self::LEASE => 'heroicon-o-document-duplicate',
        };
    }

    /**
     * Get the CSS class for timeline styling.
     */
    public function getTimelineColorClass(): string
    {
        return match ($this) {
            self::SMS => 'bg-blue-500',
            self::EMAIL => 'bg-blue-600',
            self::NOTE => 'bg-gray-500',
            self::SYSTEM => 'bg-yellow-500',
            self::FINANCIAL => 'bg-green-500',
            self::DISPUTE => 'bg-red-500',
            self::CALL => 'bg-sky-500',
            self::VISIT => 'bg-emerald-500',
            self::DOCUMENT => 'bg-slate-500',
            self::LEASE => 'bg-indigo-500',
        };
    }

    /**
     * Get the border color class for timeline cards.
     */
    public function getTimelineBorderClass(): string
    {
        return match ($this) {
            self::SMS => 'border-l-blue-500',
            self::EMAIL => 'border-l-blue-600',
            self::NOTE => 'border-l-gray-500',
            self::SYSTEM => 'border-l-yellow-500',
            self::FINANCIAL => 'border-l-green-500',
            self::DISPUTE => 'border-l-red-500',
            self::CALL => 'border-l-sky-500',
            self::VISIT => 'border-l-emerald-500',
            self::DOCUMENT => 'border-l-slate-500',
            self::LEASE => 'border-l-indigo-500',
        };
    }

    /**
     * Get options array for form selects.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    /**
     * Check if this event type is customer-facing (visible to tenant).
     */
    public function isCustomerFacing(): bool
    {
        return match ($this) {
            self::SMS, self::EMAIL, self::CALL => true,
            default => false,
        };
    }

    /**
     * Check if this event type requires follow-up.
     */
    public function requiresFollowUp(): bool
    {
        return match ($this) {
            self::DISPUTE, self::CALL, self::VISIT => true,
            default => false,
        };
    }
}
