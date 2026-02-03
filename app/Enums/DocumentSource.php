<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentSource: string implements HasLabel, HasColor, HasIcon
{
    case SCANNED = 'scanned';
    case LANDLORD_UPLOAD = 'landlord_upload';
    case SYSTEM_GENERATED = 'system_generated';
    case EMAIL_RECEIVED = 'email_received';

    public function getLabel(): string
    {
        return match ($this) {
            self::SCANNED => 'Scanned Document',
            self::LANDLORD_UPLOAD => 'Landlord Upload',
            self::SYSTEM_GENERATED => 'System Generated',
            self::EMAIL_RECEIVED => 'Received via Email',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SCANNED => 'info',
            self::LANDLORD_UPLOAD => 'warning',
            self::SYSTEM_GENERATED => 'success',
            self::EMAIL_RECEIVED => 'primary',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SCANNED => 'heroicon-o-document-magnifying-glass',
            self::LANDLORD_UPLOAD => 'heroicon-o-cloud-arrow-up',
            self::SYSTEM_GENERATED => 'heroicon-o-cog',
            self::EMAIL_RECEIVED => 'heroicon-o-envelope',
        };
    }

    /**
     * Check if source requires verification
     */
    public function requiresVerification(): bool
    {
        return in_array($this, [self::SCANNED, self::LANDLORD_UPLOAD, self::EMAIL_RECEIVED], true);
    }

    /**
     * Check if document is from external source
     */
    public function isExternal(): bool
    {
        return in_array($this, [self::LANDLORD_UPLOAD, self::EMAIL_RECEIVED], true);
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SCANNED => 'Physical document scanned and uploaded by staff',
            self::LANDLORD_UPLOAD => 'Document provided directly by landlord',
            self::SYSTEM_GENERATED => 'Document created by the system (e.g., generated lease PDF)',
            self::EMAIL_RECEIVED => 'Document received via email attachment',
        };
    }
}
