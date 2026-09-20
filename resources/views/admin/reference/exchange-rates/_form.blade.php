@php $p = $prefix; @endphp

<div class="row mb-7">
    <div class="col-md-6">
        <label class="required fw-semibold fs-6 mb-2">Base Currency</label>
        <select class="form-select form-select-solid" name="base_currency" id="{{ $p }}_base_currency" required>
            <option value="">Select Base</option>
        </select>
        <div class="text-muted fs-7 mt-1">1 unit of base = rate × quote</div>
    </div>
    <div class="col-md-6">
        <label class="required fw-semibold fs-6 mb-2">Quote Currency</label>
        <select class="form-select form-select-solid" name="quote_currency" id="{{ $p }}_quote_currency" required>
            <option value="">Select Quote</option>
        </select>
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-4">
        <label class="required fw-semibold fs-6 mb-2">Rate</label>
        <input type="number" step="0.000000000001" min="0"
               class="form-control form-control-solid font-monospace"
               name="rate" id="{{ $p }}_rate" placeholder="e.g. 3800.000000000000" required />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Markup %</label>
        <input type="number" step="0.0001" min="0" max="100"
               class="form-control form-control-solid"
               name="markup_percent" id="{{ $p }}_markup_percent" value="0" />
    </div>
    <div class="col-md-4">
        <label class="fw-semibold fs-6 mb-2">Effective Rate</label>
        <div class="form-control form-control-solid bg-light font-monospace" id="{{ $p }}_effective_preview" style="cursor:default;">—</div>
        <div class="text-muted fs-7 mt-1">Auto-computed from rate × (1 + markup)</div>
    </div>
</div>

<div class="row mb-7">
    <div class="col-md-6">
        <label class="required fw-semibold fs-6 mb-2">Effective From</label>
        <input type="datetime-local" class="form-control form-control-solid"
               name="effective_from" id="{{ $p }}_effective_from" required />
    </div>
    <div class="col-md-6">
        <label class="fw-semibold fs-6 mb-2">Effective To</label>
        <input type="datetime-local" class="form-control form-control-solid"
               name="effective_to" id="{{ $p }}_effective_to" />
        <div class="text-muted fs-7 mt-1">Leave blank for open-ended (current) rate</div>
    </div>
</div>

<div class="fv-row mb-7">
    <label class="fw-semibold fs-6 mb-2">Provider</label>
    <input type="text" class="form-control form-control-solid"
           name="provider" id="{{ $p }}_provider" placeholder="oanda, ecb, internal..." />
</div>