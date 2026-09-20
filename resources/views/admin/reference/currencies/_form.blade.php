@php $p = $prefix; @endphp

<div class="row mb-7">
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Code</label>
        <input type="text" class="form-control form-control-solid text-uppercase"
               name="code" id="{{ $p }}_code" maxlength="3" placeholder="USD" required />
    </div>
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Name</label>
        <input type="text" class="form-control form-control-solid"
               name="name" id="{{ $p }}_name" placeholder="US Dollar" required />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Symbol</label>
        <input type="text" class="form-control form-control-solid"
               name="symbol" id="{{ $p }}_symbol" maxlength="8" placeholder="$" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Exponent</label>
        <input type="number" class="form-control form-control-solid"
               name="exponent" id="{{ $p }}_exponent" min="0" max="4" value="2" required />
        <div class="text-muted fs-7 mt-1">2 = cents, 0 = whole units (UGX, JPY)</div>
    </div>
    <div class="col-md-8 d-flex align-items-end">
        <label class="form-check form-check-custom form-check-solid me-5">
            <input class="form-check-input" type="checkbox" name="is_zero_decimal" id="{{ $p }}_is_zero_decimal" value="1" />
            <span class="form-check-label fw-semibold">Zero-decimal currency</span>
        </label>
    </div>
</div>

<hr class="my-7">

<h4 class="fw-bold mb-5">Capabilities</h4>

<div class="d-flex flex-wrap gap-5 mb-7">
    @foreach ([
        'is_active' => 'Active',
        'is_presentment_currency' => 'Presentment (can charge)',
        'is_settlement_currency' => 'Settlement (can pay out)',
    ] as $field => $label)
        <label class="form-check form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" name="{{ $field }}" id="{{ $p }}_{{ $field }}" value="1" />
            <span class="form-check-label fw-semibold">{{ $label }}</span>
        </label>
    @endforeach
</div>

<hr class="my-7">

<h4 class="fw-bold mb-5">Charge Limits</h4>
<p class="text-muted fs-7 mb-5">Amounts in minor units (cents). Leave blank for no limit.</p>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Minimum Charge</label>
        <input type="number" class="form-control form-control-solid"
               name="min_charge_amount" id="{{ $p }}_min_charge_amount" min="0" placeholder="e.g. 100" />
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Maximum Charge</label>
        <input type="number" class="form-control form-control-solid"
               name="max_charge_amount" id="{{ $p }}_max_charge_amount" min="0" placeholder="e.g. 100000000" />
    </div>
</div>