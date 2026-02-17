<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING_REVIEW = 'pending_review';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case LINKED = 'linked';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING_REVIEW => 'Pending Review',
            self::IN_REVIEW => 'In Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LINKED => 'Linked to Lease',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING_REVIEW => 'warning',
            self::IN_REVIEW => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::LINKED => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING_REVIEW => 'heroicon-o-clock',
            self::IN_REVIEW => 'heroicon-o-eye',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::LINKED => 'heroicon-o-link',
        };
    }

    /**
     * Get valid transitions from current status
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::PENDING_REVIEW => [self::IN_REVIEW, self::REJECTED],
            self::IN_REVIEW => [self::APPROVED, self::REJECTED, self::PENDING_REVIEW],
            self::APPROVED => [self::LINKED, self::REJECTED],
            self::REJECTED => [self::PENDING_REVIEW], // Can re-upload/resubmit
            self::LINKED => [], // Final state
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->validTransitions(), true);
    }

    /**
     * Check if document requires action
     */
    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING_REVIEW, self::IN_REVIEW, self::APPROVED], true);
    }

    /**
     * Check if document is in a final state
     */
    public function isFinal(): bool
    {
        return $this === self::LINKED;
    }

    /**
     * Check if document can be edited
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::PENDING_REVIEW, self::REJECTED], true);
    }

    /**
     * Check if document can be deleted
     */
    public function canDelete(): bool
    {
        return in_array($this, [self::PENDING_REVIEW, self::REJECTED], true);
    }

    /**
     * Get status description for users
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING_REVIEW => 'Document uploaded and waiting for review',
            self::IN_REVIEW => 'Document is being reviewed by an administrator',
            self::APPROVED => 'Document approved, ready to link to a lease',
            self::REJECTED => 'Document rejected - see reason for details',
            self::LINKED => 'Document successfully linked to a lease record',
        };
    }
}
