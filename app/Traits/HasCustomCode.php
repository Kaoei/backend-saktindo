<?php

namespace App\Traits;

trait HasCustomCode
{
    /**
     * Boot the trait — registers the creating event to auto-generate a custom code.
     */
    protected static function bootHasCustomCode(): void
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $count = static::withoutGlobalScopes()->count() + 1;
                $model->id = static::CODE_PREFIX
                    . '-' . now()->format('Ym')
                    . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
