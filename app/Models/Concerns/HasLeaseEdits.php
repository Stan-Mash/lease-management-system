<?php

namespace App\Models\Concerns;

use App\Actions\Lease\RecordLeaseEdit;
use App\Models\LeaseEdit;

/**
 * Trait for models that track document edits.
 */
trait HasLeaseEdits
{
    /**
     * Record an edit to the lease document.
     *
     * @param string $editType One of: clause_added, clause_removed, clause_modified, other
     * @param string|null $sectionAffected Which clause/section was edited
     * @param string|null $originalText Text before edit (null if new)
     * @param string|null $newText Text after edit (null if removed)
     * @param string|null $reason Why the edit was made
     * @return LeaseEdit
     */
    public function recordEdit(
        string $editType,
        ?string $sectionAffected = null,
        ?string $originalText = null,
        ?string $newText = null,
        ?string $reason = null
    ): LeaseEdit {
        return app(RecordLeaseEdit::class)->execute(
            $this,
            $editType,
            $sectionAffected,
            $originalText,
            $newText,
            $reason
        );
    }

    /**
     * Record multiple edits in a batch.
     *
     * @param array $edits Array of edit data
     * @return array<LeaseEdit>
     */
    public function recordEditsBatch(array $edits): array
    {
        return app(RecordLeaseEdit::class)->executeBatch($this, $edits);
    }

    /**
     * Get the current document version.
     */
    public function getDocumentVersion(): int
    {
        return $this->document_version ?? 1;
    }

    /**
     * Get all edits for a specific document version.
     */
    public function getEditsForVersion(int $version)
    {
        return $this->edits()->where('document_version', $version)->get();
    }

    /**
     * Get the edit history grouped by version.
     */
    public function getEditHistory()
    {
        return $this->edits()
            ->orderBy('document_version', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('document_version');
    }
}
