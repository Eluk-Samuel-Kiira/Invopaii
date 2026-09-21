<?php

namespace App\Models\Webhook;

use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    protected $fillable = ['name', 'resource', 'description', 'category', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }
}