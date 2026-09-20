@php $p = $prefix; @endphp

<h4 class="fw-bold mb-5">Identity</h4>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="required fw-semibold fs-6 mb-2">Trading Name</label>
        <input type="text" class="form-control form-control-solid" name="name" id="{{ $p }}_name" required />
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Legal Name</label>
        <input type="text" class="form-control form-control-solid" name="legal_name" id="{{ $p }}_legal_name" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Slug</label>
        <input type="text" class="form-control form-control-solid" name="slug" id="{{ $p }}_slug" placeholder="auto-generated if blank" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Email</label>
        <input type="email" class="form-control form-control-solid" name="email" id="{{ $p }}_email" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Support Email</label>
        <input type="email" class="form-control form-control-solid" name="support_email" id="{{ $p }}_support_email" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Support Phone</label>
        <input type="text" class="form-control form-control-solid" name="support_phone" id="{{ $p }}_support_phone" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Website</label>
        <input type="url" class="form-control form-control-solid" name="website" id="{{ $p }}_website" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Brand Color</label>
        <input type="text" class="form-control form-control-solid" name="brand_color" id="{{ $p }}_brand_color" placeholder="#6E3FE7" />
    </div>
</div>

<hr class="my-7">
<h4 class="fw-bold mb-5">Registration</h4>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Country</label>
        <select class="form-select form-select-solid" name="country_id" id="{{ $p }}_country_id" required></select>
    </div>
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Business Type</label>
        <select class="form-select form-select-solid" name="business_type" id="{{ $p }}_business_type" required></select>
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Industry</label>
        <input type="text" class="form-control form-control-solid" name="industry" id="{{ $p }}_industry" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">MCC</label>
        <input type="text" class="form-control form-control-solid" name="mcc" id="{{ $p }}_mcc" />
    </div>
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Registration #</label>
        <input type="text" class="form-control form-control-solid" name="registration_number" id="{{ $p }}_registration_number" />
    </div>
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Tax ID</label>
        <input type="text" class="form-control form-control-solid" name="tax_identification_number" id="{{ $p }}_tax_identification_number" />
    </div>
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Incorporated On</label>
        <input type="date" class="form-control form-control-solid" name="incorporated_on" id="{{ $p }}_incorporated_on" />
    </div>
</div>

<hr class="my-7">
<h4 class="fw-bold mb-5">Address</h4>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Address Line 1</label>
        <input type="text" class="form-control form-control-solid" name="address_line1" id="{{ $p }}_address_line1" />
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Address Line 2</label>
        <input type="text" class="form-control form-control-solid" name="address_line2" id="{{ $p }}_address_line2" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">City</label>
        <input type="text" class="form-control form-control-solid" name="city" id="{{ $p }}_city" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">State</label>
        <input type="text" class="form-control form-control-solid" name="state" id="{{ $p }}_state" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Postal Code</label>
        <input type="text" class="form-control form-control-solid" name="postal_code" id="{{ $p }}_postal_code" />
    </div>
</div>

<hr class="my-7">
<h4 class="fw-bold mb-5">Money</h4>

<div class="row mb-7">
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Default Currency</label>
        <input type="text" class="form-control form-control-solid text-uppercase" name="default_currency" id="{{ $p }}_default_currency" maxlength="3" required />
    </div>
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Settlement Currency</label>
        <input type="text" class="form-control form-control-solid text-uppercase" name="settlement_currency" id="{{ $p }}_settlement_currency" maxlength="3" />
    </div>
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Timezone</label>
        <input type="text" class="form-control form-control-solid" name="timezone" id="{{ $p }}_timezone" value="UTC" required />
    </div>
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Statement Descriptor</label>
        <input type="text" class="form-control form-control-solid" name="statement_descriptor" id="{{ $p }}_statement_descriptor" maxlength="22" />
    </div>
</div>

<hr class="my-7">
<h4 class="fw-bold mb-5">Risk & Commercials</h4>

<div class="row mb-7">
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Risk Level</label>
        <select class="form-select form-select-solid" name="risk_level" id="{{ $p }}_risk_level" required></select>
    </div>
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Payout Schedule</label>
        <select class="form-select form-select-solid" name="payout_schedule" id="{{ $p }}_payout_schedule" required></select>
    </div>
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Payout Delay (days)</label>
        <input type="number" class="form-control form-control-solid" name="payout_delay_days" id="{{ $p }}_payout_delay_days" value="2" min="0" max="30" required />
    </div>
    <div class="col-md-3">
        <label class="required fw-semibold fs-6 mb-2">Reserve Hold (days)</label>
        <input type="number" class="form-control form-control-solid" name="reserve_hold_days" id="{{ $p }}_reserve_hold_days" value="0" min="0" max="365" required />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-3">
        <label class="fw-semibold fs-6 mb-2">Reserve %</label>
        <input type="number" step="0.01" class="form-control form-control-solid" name="reserve_percent" id="{{ $p }}_reserve_percent" value="0" min="0" max="100" />
    </div>
    <div class="col-md-9">
        <label class="required fw-semibold fs-6 mb-2">Owner</label>
        <select class="form-select form-select-solid" name="owner_id" id="{{ $p }}_owner_id" required></select>
    </div>
</div>