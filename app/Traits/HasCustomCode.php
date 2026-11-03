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
        $yearMonth = now()->format('Ym');

        $query = static::withoutGlobalScopes();
        if (method_exists(static::class, 'withTrashed')) {
            $query = static::withTrashed();
        }

        // Find the maximum sequence number from existing IDs with this prefix
        $maxNum = 0;
        try {
            $maxNum = (int) (clone $query)
                ->where('id', 'like', $prefix . '-%')
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED)) as max_num")
                ->value('max_num');
        } catch (\Throwable $e) {
            $maxNum = 0;
        }

        $totalCount = (clone $query)->count();
        $nextNum = max($maxNum + 1, $totalCount + 1);

        $candidateId = $prefix . '-' . $yearMonth . '-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

        // Ensure collision safety in case of gaps, deletions, or manual entries
        while ((clone $query)->where('id', $candidateId)->exists()) {
            $nextNum++;
            $candidateId = $prefix . '-' . $yearMonth . '-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        }

        return $candidateId;
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
