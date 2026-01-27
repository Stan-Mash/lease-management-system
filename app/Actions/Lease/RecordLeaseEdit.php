<?php

namespace App\Actions\Lease;

use App\Models\Lease;
use App\Models\LeaseEdit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for recording edits to a lease document.
 */
class RecordLeaseEdit
{
    /**
     * Execute the edit recording.
     *
     * @param Lease $lease
     * @param string $editType One of: clause_added, clause_removed, clause_modified, other
     * @param string|null $sectionAffected Which clause/section was edited
     * @param string|null $originalText Text before edit (null if new)
     * @param string|null $newText Text after edit (null if removed)
     * @param string|null $reason Why the edit was made
     * @return LeaseEdit
     */
    public function execute(
        Lease $lease,
        string $editType,
        ?string $sectionAffected = null,
        ?string $originalText = null,
        ?string $newText = null,
        ?string $reason = null
    ): LeaseEdit {
        return DB::transaction(function () use ($lease, $editType, $sectionAffected, $originalText, $newText, $reason) {
            // Increment document version
            $lease->document_version = ($lease->document_version ?? 0) + 1;
            $lease->save();

            return $lease->edits()->create([
                'edited_by' => Auth::id(),
                'edit_type' => $editType,
                'section_affected' => $sectionAffected,
                'original_text' => $originalText,
                'new_text' => $newText,
                'reason' => $reason,
                'document_version' => $lease->document_version,
            ]);
        });
    }

    /**
     * Record multiple edits in a batch (single version increment).
     *
     * @param Lease $lease
     * @param array $edits Array of edit data
     * @return array<LeaseEdit>
     */
    public function executeBatch(Lease $lease, array $edits): array
    {
        return DB::transaction(function () use ($lease, $edits) {
            // Increment document version once for the batch
            $lease->document_version = ($lease->document_version ?? 0) + 1;
            $lease->save();

            $createdEdits = [];

            foreach ($edits as $edit) {
                $createdEdits[] = $lease->edits()->create([
                    'edited_by' => Auth::id(),
                    'edit_type' => $edit['edit_type'],
                    'section_affected' => $edit['section_affected'] ?? null,
                    'original_text' => $edit['original_text'] ?? null,
                    'new_text' => $edit['new_text'] ?? null,
                    'reason' => $edit['reason'] ?? null,
                    'document_version' => $lease->document_version,
                ]);
            }

            return $createdEdits;
        });
    }
}
