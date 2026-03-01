<?php

namespace App\Rules;

use App\Services\TemplateSanitizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeTemplateRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            app(TemplateSanitizer::class)->assertSafe($value);
        } catch (\InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }
}
