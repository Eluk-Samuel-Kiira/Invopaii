@php $p = $prefix; @endphp

<div class="row mb-7">
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">ISO2</label>
        <input type="text" class="form-control form-control-solid text-uppercase"
               name="iso2" id="{{ $p }}_iso2" maxlength="2" required />
    </div>
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">ISO3</label>
        <input type="text" class="form-control form-control-solid text-uppercase"
               name="iso3" id="{{ $p }}_iso3" maxlength="3" required />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Flag Emoji</label>
        <input type="text" class="form-control form-control-solid"
               name="flag_emoji" id="{{ $p }}_flag_emoji" placeholder="🇺🇬" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="required fw-semibold fs-6 mb-2">Name</label>
        <input type="text" class="form-control form-control-solid"
               name="name" id="{{ $p }}_name" required />
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Official Name</label>
        <input type="text" class="form-control form-control-solid"
               name="official_name" id="{{ $p }}_official_name" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Phone Code</label>
        <input type="text" class="form-control form-control-solid"
               name="phone_code" id="{{ $p }}_phone_code" placeholder="256" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Default Currency</label>
        <input type="text" class="form-control form-control-solid text-uppercase"
               name="default_currency" id="{{ $p }}_default_currency"
               maxlength="3" placeholder="UGX" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Region</label>
        <input type="text" class="form-control form-control-solid"
               name="region" id="{{ $p }}_region" placeholder="Africa" />
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Subregion</label>
        <input type="text" class="form-control form-control-solid"
               name="subregion" id="{{ $p }}_subregion" placeholder="Eastern Africa" />
    </div>
</div>

<hr class="my-7">

<h4 class="fw-bold mb-5">Market Posture</h4>

<div class="d-flex flex-wrap gap-5 mb-7">
    @foreach ([
        'is_supported' => 'Supported',
        'collections_enabled' => 'Collections',
        'payouts_enabled' => 'Payouts',
        'is_high_risk' => 'High Risk',
        'is_sanctioned' => 'Sanctioned',
    ] as $field => $label)
        <label class="form-check form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox"
                   name="{{ $field }}" id="{{ $p }}_{{ $field }}" value="1" />
            <span class="form-check-label fw-semibold">{{ $label }}</span>
        </label>
    @endforeach
</div>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Supported Payment Methods</label>
        <select class="form-select form-select-solid"
                name="supported_payment_methods[]"
                id="{{ $p }}_supported_payment_methods" multiple size="5">
            @foreach (['card','mobile_money','bank_transfer','cash','crypto'] as $m)
                <option value="{{ $m }}">{{ ucwords(str_replace('_', ' ', $m)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Required Business Documents</label>
        <select class="form-select form-select-solid"
                name="required_business_documents[]"
                id="{{ $p }}_required_business_documents" multiple size="5">
            @foreach (['certificate_of_incorporation','tax_id','directors_id','proof_of_address','bank_statement'] as $d)
                <option value="{{ $d }}">{{ ucwords(str_replace('_', ' ', $d)) }}</option>
            @endforeach
        </select>
    </div>
</div>