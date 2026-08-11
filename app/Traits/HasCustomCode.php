<?php

namespace App\Traits;

trait HasCustomCode
{
    /**
     * Generate custom code/ID manually.
     */
    public static function generateId(): string
    {
        $prefix = defined('static::CODE_PREFIX') ? static::CODE_PREFIX : 'CODE';
        $count = static::withoutGlobalScopes()->count() + 1;
        return $prefix
            . '-' . now()->format('Ym')
            . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot the trait — registers the creating event to auto-generate a custom code.
     */
    protected static function bootHasCustomCode(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = static::generateId();
            }
        });
    }
}
