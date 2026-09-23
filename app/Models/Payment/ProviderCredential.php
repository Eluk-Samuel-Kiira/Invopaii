<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_provider_id', 'company_id', 'mode',
        'label', 'public_key', 'secret_key', 'webhook_secret',
        'merchant_account_id', 'extra', 'is_active', 'last_verified_at',
    ];

    protected $casts = [
        'public_key' => 'encrypted',
        'secret_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'extra' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = ['secret_key', 'webhook_secret', 'extra'];

    /* ---------- Relations ---------- */

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /* ---------- Accessors ---------- */

    public function getMaskedSecretAttribute(): ?string
    {
        if (!$this->secret_key) return null;
        return str_repeat('•', 16) . substr($this->secret_key, -4);
    }

    public function getMaskedWebhookSecretAttribute(): ?string
    {
        if (!$this->webhook_secret) return null;
        return str_repeat('•', 16) . substr($this->webhook_secret, -4);
    }

    public function getMaskedPublicKeyAttribute(): ?string
    {
        if (!$this->public_key) return null;
        $len = strlen($this->public_key);
        if ($len <= 8) return str_repeat('•', $len);
        return substr($this->public_key, 0, 4) . str_repeat('•', 12) . substr($this->public_key, -4);
    }
}