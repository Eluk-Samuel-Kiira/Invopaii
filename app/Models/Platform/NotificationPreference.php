<?php

namespace App\Models\Platform;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'event_category',
        'email_enabled', 'sms_enabled', 'push_enabled', 'in_app_enabled',
        'digest_frequency',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }

    public static function forUserCategory(int $userId, int $companyId, string $category): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'company_id' => $companyId, 'event_category' => $category],
            ['email_enabled' => true, 'in_app_enabled' => true, 'push_enabled' => true, 'digest_frequency' => 'instant']
        );
    }
}