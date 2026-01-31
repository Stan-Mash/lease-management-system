<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateVariableDefinition extends Model
{
    protected $fillable = [
        'variable_name',
        'display_name',
        'category',
        'description',
        'data_type',
        'format_options',
        'is_required',
        'eloquent_path',
        'helper_method',
        'sample_value',
    ];

    protected $casts = [
        'format_options' => 'array',
        'is_required' => 'boolean',
    ];

    // Scope by category
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Get formatted value
    public function formatValue($value)
    {
        return match ($this->data_type) {
            'money' => 'Ksh ' . number_format($value, 2),
            'date' => $value?->format($this->format_options['date_format'] ?? 'd/m/Y'),
            'number' => number_format($value, $this->format_options['decimal_places'] ?? 0),
            default => $value,
        };
    }
}
