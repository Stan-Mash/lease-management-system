<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAudit extends Model
{
    // Action constants
    public const ACTION_UPLOAD = 'upload';

    public const ACTION_VIEW = 'view';

    public const ACTION_DOWNLOAD = 'download';

    public const ACTION_EDIT = 'edit';

    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_LINK = 'link';

    public const ACTION_UNLINK = 'unlink';

    public const ACTION_VERIFY = 'verify';

    public const ACTION_REPLACE = 'replace';

    public const ACTION_DELETE = 'delete';

    public const ACTION_RESTORE = 'restore';

    public const ACTION_RESUBMIT = 'resubmit';

    // Category constants
    public const CATEGORY_ACCESS = 'access';

    public const CATEGORY_MODIFICATION = 'modification';

    public const CATEGORY_WORKFLOW = 'workflow';

    public const CATEGORY_INTEGRITY = 'integrity';

    protected $table = 'document_audit_trail';

    protected $fillable = [
        'lease_document_id',
        'user_id',
        'action',
        'action_category',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'file_hash',
        'integrity_verified',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'integrity_verified' => 'boolean',
    ];

    // Relationships
    public function document(): BelongsTo
    {
        return $this->belongsTo(LeaseDocument::class, 'lease_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get action labels for display
     */
    public static function getActionLabels(): array
    {
        return [
            self::ACTION_UPLOAD => 'Document Uploaded',
            self::ACTION_VIEW => 'Document Viewed',
            self::ACTION_DOWNLOAD => 'Document Downloaded',
            self::ACTION_EDIT => 'Document Edited',
            self::ACTION_APPROVE => 'Document Approved',
            self::ACTION_REJECT => 'Document Rejected',
            self::ACTION_LINK => 'Linked to Lease',
            self::ACTION_UNLINK => 'Unlinked from Lease',
            self::ACTION_VERIFY => 'Integrity Verified',
            self::ACTION_REPLACE => 'Document Replaced',
            self::ACTION_DELETE => 'Document Deleted',
            self::ACTION_RESTORE => 'Document Restored',
            self::ACTION_RESUBMIT => 'Document Resubmitted',
        ];
    }

    /**
     * Get action icons
     */
    public static function getActionIcons(): array
    {
        return [
            self::ACTION_UPLOAD => 'heroicon-o-cloud-arrow-up',
            self::ACTION_VIEW => 'heroicon-o-eye',
            self::ACTION_DOWNLOAD => 'heroicon-o-arrow-down-tray',
            self::ACTION_EDIT => 'heroicon-o-pencil',
            self::ACTION_APPROVE => 'heroicon-o-check-circle',
            self::ACTION_REJECT => 'heroicon-o-x-circle',
            self::ACTION_LINK => 'heroicon-o-link',
            self::ACTION_UNLINK => 'heroicon-o-link-slash',
            self::ACTION_VERIFY => 'heroicon-o-shield-check',
            self::ACTION_REPLACE => 'heroicon-o-arrow-path',
            self::ACTION_DELETE => 'heroicon-o-trash',
            self::ACTION_RESTORE => 'heroicon-o-arrow-uturn-left',
            self::ACTION_RESUBMIT => 'heroicon-o-arrow-path-rounded-square',
        ];
    }

    /**
     * Get action colors
     */
    public static function getActionColors(): array
    {
        return [
            self::ACTION_UPLOAD => 'info',
            self::ACTION_VIEW => 'gray',
            self::ACTION_DOWNLOAD => 'gray',
            self::ACTION_EDIT => 'warning',
            self::ACTION_APPROVE => 'success',
            self::ACTION_REJECT => 'danger',
            self::ACTION_LINK => 'primary',
            self::ACTION_UNLINK => 'warning',
            self::ACTION_VERIFY => 'success',
            self::ACTION_REPLACE => 'warning',
            self::ACTION_DELETE => 'danger',
            self::ACTION_RESTORE => 'success',
            self::ACTION_RESUBMIT => 'info',
        ];
    }

    /**
     * Get the label for this action
     */
    public function getActionLabelAttribute(): string
    {
        return self::getActionLabels()[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get the icon for this action
     */
    public function getActionIconAttribute(): string
    {
        return self::getActionIcons()[$this->action] ?? 'heroicon-o-document';
    }

    /**
     * Get the color for this action
     */
    public function getActionColorAttribute(): string
    {
        return self::getActionColors()[$this->action] ?? 'gray';
    }

    /**
     * Create an audit entry
     */
    public static function log(
        LeaseDocument $document,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?bool $integrityVerified = null,
    ): self {
        $category = match ($action) {
            self::ACTION_VIEW, self::ACTION_DOWNLOAD => self::CATEGORY_ACCESS,
            self::ACTION_EDIT, self::ACTION_REPLACE => self::CATEGORY_MODIFICATION,
            self::ACTION_APPROVE, self::ACTION_REJECT, self::ACTION_LINK,
            self::ACTION_UNLINK, self::ACTION_RESUBMIT => self::CATEGORY_WORKFLOW,
            self::ACTION_VERIFY => self::CATEGORY_INTEGRITY,
            default => self::CATEGORY_ACCESS,
        };

        return self::create([
            'lease_document_id' => $document->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'action_category' => $category,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'file_hash' => $document->file_hash,
            'integrity_verified' => $integrityVerified,
        ]);
    }
}
