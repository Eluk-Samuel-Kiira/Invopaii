<?php

namespace App\Models\Payment;

use App\Models\Catalog\Discount;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Payment\PaymentLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'customer_id', 'created_by_id', 'mode',
        'number', 'access_token',
        'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'company_snapshot',
        'currency', 'subtotal', 'discount_total', 'tax_total', 'shipping_total',
        'total', 'amount_paid', 'amount_due', 'amount_refunded',
        'discount_id',
        'status', 'collection_method', 'allow_partial_payment',
        'issue_date', 'due_date', 'payment_terms_days',
        'finalized_at', 'sent_at', 'first_viewed_at', 'paid_at', 'voided_at', 'view_count',
        'hosted_url', 'pdf_path', 'payment_link_id',
        'notes', 'terms', 'internal_notes',
        'is_recurring', 'parent_invoice_id', 'subscription_id',
        'recurrence_interval', 'recurrence_interval_count', 'next_issue_date',
        'auto_reminders_enabled', 'reminders_sent',
        'metadata',
    ];

    protected $casts = [
        'customer_address' => 'array',
        'company_snapshot' => 'array',
        'metadata' => 'array',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'tax_total' => 'integer',
        'shipping_total' => 'integer',
        'total' => 'integer',
        'amount_paid' => 'integer',
        'amount_due' => 'integer',
        'amount_refunded' => 'integer',
        'allow_partial_payment' => 'boolean',
        'is_recurring' => 'boolean',
        'auto_reminders_enabled' => 'boolean',
        'view_count' => 'integer',
        'reminders_sent' => 'integer',
        'payment_terms_days' => 'integer',
        'recurrence_interval_count' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
        'next_issue_date' => 'date',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $i) {
            $i->uuid ??= (string) Str::uuid();
            $i->public_id ??= 'inv_' . Str::lower(Str::random(24));
            $i->access_token ??= Str::random(64);
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by_id'); }
    public function discount() { return $this->belongsTo(Discount::class); }
    public function paymentLink() { return $this->belongsTo(PaymentLink::class); }
    public function parentInvoice() { return $this->belongsTo(Invoice::class, 'parent_invoice_id'); }
    public function childInvoices() { return $this->hasMany(Invoice::class, 'parent_invoice_id'); }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function reminders()
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    public function checkoutSessions()
    {
        return $this->hasMany(\App\Models\PaymentLink\CheckoutSession::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('number', 'like', "%{$term}%")
              ->orWhere('public_id', 'like', "%{$term}%")
              ->orWhere('customer_name', 'like', "%{$term}%")
              ->orWhere('customer_email', 'like', "%{$term}%");
        });
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'partially_paid', 'past_due']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['open', 'partially_paid'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString());
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft'           => ['label' => 'Draft',           'tone' => 'secondary'],
            'open'            => ['label' => 'Open',            'tone' => 'info'],
            'paid'            => ['label' => 'Paid',            'tone' => 'success'],
            'partially_paid'  => ['label' => 'Partially Paid',  'tone' => 'warning'],
            'past_due'        => ['label' => 'Past Due',        'tone' => 'danger'],
            'void'            => ['label' => 'Void',            'tone' => 'secondary'],
            'uncollectible'   => ['label' => 'Uncollectible',   'tone' => 'dark'],
            default           => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, ['draft', 'open']);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && in_array($this->status, ['open', 'partially_paid']);
    }

    public function getHostedUrlWithTokenAttribute(): ?string
    {
        if (!$this->hosted_url) return null;
        return $this->hosted_url . '?token=' . $this->access_token;
    }
}