<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DisputeEvidence extends Model
{
    protected $fillable = [
        'uuid', 'dispute_id', 'submitted_by_id',
        'field', 'text_value',
        'disk', 'file_path', 'original_filename', 'mime_type', 'size_bytes',
        'is_submitted', 'submitted_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_submitted' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($e) => $e->uuid ??= (string) Str::uuid());
    }

    public function dispute() { return $this->belongsTo(Dispute::class); }
    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by_id'); }

    public function getFieldLabelAttribute(): string
    {
        return match ($this->field) {
            'receipt'                  => 'Receipt',
            'shipping_documentation'   => 'Shipping Documentation',
            'customer_communication'   => 'Customer Communication',
            'refund_policy'            => 'Refund Policy',
            'service_date'             => 'Service Date',
            'cancellation_policy'      => 'Cancellation Policy',
            'uncategorized_file'       => 'Uncategorized File',
            'customer_signature'       => 'Customer Signature',
            default                    => ucfirst(str_replace('_', ' ', $this->field)),
        };
    }

    public function getHasFileAttribute(): bool
    {
        return !empty($this->file_path);
    }
}