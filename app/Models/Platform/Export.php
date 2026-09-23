<?php

namespace App\Models\Platform;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Export extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'requested_by_id', 'mode',
        'type', 'format', 'filters', 'columns',
        'status', 'row_count', 'file_size_bytes',
        'disk', 'path', 'download_token', 'progress_percent', 'failure_reason',
        'started_at', 'completed_at', 'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'row_count' => 'integer',
        'file_size_bytes' => 'integer',
        'progress_percent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Export $e) {
            $e->uuid ??= (string) Str::uuid();
            $e->download_token ??= Str::random(64);
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by_id'); }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'queued'     => ['label' => 'Queued',     'tone' => 'secondary'],
            'processing' => ['label' => 'Processing', 'tone' => 'info'],
            'completed'  => ['label' => 'Completed',  'tone' => 'success'],
            'failed'     => ['label' => 'Failed',     'tone' => 'danger'],
            'expired'    => ['label' => 'Expired',    'tone' => 'warning'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getIsDownloadableAttribute(): bool
    {
        return $this->status === 'completed'
            && $this->path
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function getSizeHumanAttribute(): ?string
    {
        if (!$this->file_size_bytes) return null;
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size_bytes;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }
}