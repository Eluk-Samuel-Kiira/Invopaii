<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'company_id', 'mode', 'key', 'method', 'endpoint',
        'request_hash', 'status', 'response_code', 'response_body',
        'resource_type', 'resource_id', 'locked_at', 'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}