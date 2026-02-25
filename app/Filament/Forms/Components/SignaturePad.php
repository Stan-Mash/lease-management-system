<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * A drawn-signature canvas field for Filament modals.
 *
 * Renders a SignaturePad canvas (signature_pad@4 from CDN).
 * Stores the drawn signature as a base64 PNG data URI in the form state
 * under the given field name.
 *
 * Usage:
 *   SignaturePad::make('manager_signature_data')
 *       ->label('Draw Your Signature')
 *       ->required()
 */
class SignaturePad extends Field
{
    protected string $view = 'filament.forms.components.signature-pad';
}
