<?php

namespace App\Models\Webhook;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'type', 'resource_type', 'resource_id', 'resource_public_id',
        'data', 'previous_attributes', 'api_version',
        'origin', 'triggered_by_id', 'pending_webhooks',
    ];

    protected $casts = [
        'data' => 'array',
        'previous_attributes' => 'array',
        'pending_webhooks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (Event $e) {
            $e->uuid ??= (string) Str::uuid();
            $e->public_id ??= 'evt_' . Str::lower(Str::random(24));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }

    public function resource()
    {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Canonical payload exposed in the webhook body.
     */
    public function toWebhookPayload(): array
    {
        return [
            'id' => $this->public_id,
            'type' => $this->type,
            'api_version' => $this->api_version,
            'created' => $this->created_at?->toIso8601String(),
            'data' => [
                'object' => $this->data,
                'previous_attributes' => $this->previous_attributes,
            ],
            'resource' => [
                'type' => $this->resource_type,
                'id' => $this->resource_public_id,
            ],
        ];
    }
}