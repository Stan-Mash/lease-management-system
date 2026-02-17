<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentQuality: string implements HasColor, HasIcon, HasLabel
{
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';
    case ILLEGIBLE = 'illegible';

    public function getLabel(): string
    {
        return match ($this) {
            self::GOOD => 'Good Quality',
            self::FAIR => 'Fair Quality',
            self::POOR => 'Poor Quality',
            self::ILLEGIBLE => 'Illegible',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GOOD => 'success',
            self::FAIR => 'warning',
            self::POOR => 'danger',
            self::ILLEGIBLE => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::GOOD => 'heroicon-o-check-badge',
            self::FAIR => 'heroicon-o-exclamation-triangle',
            self::POOR => 'heroicon-o-exclamation-circle',
            self::ILLEGIBLE => 'heroicon-o-no-symbol',
        };
    }

    /**
     * Get description for tooltip/help text
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GOOD => 'Clear, readable text. No issues with scan quality.',
            self::FAIR => 'Mostly readable. Some minor quality issues but usable.',
            self::POOR => 'Difficult to read. May need re-scanning or manual verification.',
            self::ILLEGIBLE => 'Cannot be read. Document needs to be re-scanned or sourced again.',
        };
    }

    /**
     * Check if quality is acceptable for processing
     */
    public function isAcceptable(): bool
    {
        return in_array($this, [self::GOOD, self::FAIR], true);
    }

    /**
     * Check if document should be flagged for attention
     */
    public function requiresAttention(): bool
    {
        return in_array($this, [self::POOR, self::ILLEGIBLE], true);
    }

    /**
     * Suggest action based on quality
     */
    public function getSuggestedAction(): ?string
    {
        return match ($this) {
            self::GOOD => null,
            self::FAIR => 'Consider re-scanning if critical details are unclear.',
            self::POOR => 'Re-scan recommended. Verify key details manually.',
            self::ILLEGIBLE => 'Must re-scan or obtain new copy of document.',
        };
    }
}
