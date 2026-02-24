<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposal extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'PRO';

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_PENDING    = 'pending';
    public const STATUS_FOLLOW_UP  = 'follow_up';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_DECLINED   = 'declined';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_FOLLOW_UP,
        self::STATUS_APPROVED,
        self::STATUS_DECLINED,
    ];

    protected $fillable = [
        'client_id',
        'created_by',
        'title',
        'description',
        'amount',
        'status',
        'follow_up_deadline',
        'email_sent_at',
        'notes',
    ];

    protected $casts = [
        'follow_up_deadline' => 'date',
        'email_sent_at'      => 'datetime',
        'amount'             => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'Pending',
            self::STATUS_FOLLOW_UP => 'Follow Up',
            self::STATUS_APPROVED  => 'Approved',
            self::STATUS_DECLINED  => 'Declined',
            default                => ucfirst($this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'bg-secondary',
            self::STATUS_FOLLOW_UP => 'bg-warning',
            self::STATUS_APPROVED  => 'bg-success',
            self::STATUS_DECLINED  => 'bg-danger',
            default                => 'bg-secondary',
        };
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, [self::STATUS_APPROVED, self::STATUS_DECLINED], true)
            && $this->follow_up_deadline !== null
            && $this->follow_up_deadline->isPast();
    }
}
