<script>
/* ═══════════════════════════════════════════════════════
   COMPLIANCE SCRIPT
   Used by: companies index + compliance queue
   Covers: representatives, documents, verification checks
   ═══════════════════════════════════════════════════════ */

(function () {
    'use strict';

    let complianceCompanyId = null;
    let complianceRepresentatives = [];

    function h(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function qsEscape(str) {
        return String(str || '').replace(/'/g, "\\'");
    }

    /* ─────────────────────────────────────────────
       OPEN COMPLIANCE MODAL
       ───────────────────────────────────────────── */

    window.openCompliance = function (companyId, companyName) {
        complianceCompanyId = companyId;

        const nameEl = document.getElementById('compliance_company_name');
        if (nameEl) nameEl.textContent = companyName || 'Company';

        loadRepresentatives(companyId);
        loadDocuments(companyId);
        loadChecks(companyId);

        new bootstrap.Modal(document.getElementById('kt_modal_compliance')).show();
    };

    /* ═════════════════════════════════════════════
       REPRESENTATIVES
       ═════════════════════════════════════════════ */

    function loadRepresentatives(companyId) {
        const loading = document.getElementById('comp_reps_loading');
        const empty = document.getElementById('comp_reps_empty');
        const container = document.getElementById('comp_reps_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/representatives`)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('d-none');
                complianceRepresentatives = data.data || [];

                const countEl = document.getElementById('comp_reps_count');
                if (countEl) countEl.textContent = complianceRepresentatives.length;

                if (!complianceRepresentatives.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderRepresentatives();
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load representatives');
            });
    }

    function renderRepresentatives() {
        const body = document.getElementById('comp_reps_body');
        if (!body) return;
        body.innerHTML = '';

        complianceRepresentatives.forEach(r => {
            const rolesHtml = (r.roles && r.roles.length)
                ? r.roles.map(x => `<span class="badge badge-light-primary fs-8 me-1">${h(x)}</span>`).join('')
                : '<span class="text-muted">—</span>';

            const contactHtml = [
                r.email ? `<div>${h(r.email)}</div>` : '',
                r.phone ? `<div class="text-muted fs-8">${h(r.phone)}</div>` : '',
            ].join('') || '<span class="text-muted">—</span>';

            const idHtml = r.id_document_type
                ? `<div class="fs-8 text-muted text-uppercase">${h(r.id_document_type.replace('_', ' '))}</div>
                   <div class="font-monospace fs-8">${h(r.id_document_masked || '—')}</div>`
                : '<span class="text-muted">—</span>';

            const ownershipHtml = r.ownership_percent
                ? `${parseFloat(r.ownership_percent).toFixed(2)}%`
                : '<span class="text-muted">—</span>';

            const kycTone = r.kyc_badge ? r.kyc_badge.tone : 'secondary';
            const kycLabel = r.kyc_badge ? r.kyc_badge.label : 'Unknown';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="fw-bold">${h(r.full_name)}</div>
                    ${r.job_title ? `<div class="text-muted fs-8">${h(r.job_title)}</div>` : ''}
                </td>
                <td>${contactHtml}</td>
                <td>${rolesHtml}</td>
                <td>${ownershipHtml}</td>
                <td>${idHtml}</td>
                <td><span class="badge badge-light-${kycTone}">${h(kycLabel)}</span></td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="editRepresentative(${r.id})" title="Edit" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-setting-3 fs-4"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteRepresentative(${r.id}, '${qsEscape(r.full_name)}')" title="Remove" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    window.openAddRepresentative = function () {
        document.getElementById('rep_modal_title').textContent = 'Add Representative';
        document.getElementById('rep_id').value = '';
        document.getElementById('rep_company_id').value = complianceCompanyId;
        document.getElementById('representativeForm').reset();
        new bootstrap.Modal(document.getElementById('kt_modal_representative')).show();
    };

    window.editRepresentative = function (id) {
        const r = complianceRepresentatives.find(x => x.id === id);
        if (!r) return;

        const set = (field, value) => {
            const el = document.getElementById(field);
            if (el) el.value = value || '';
        };

        document.getElementById('rep_modal_title').textContent = 'Edit Representative';
        set('rep_id', r.id);
        set('rep_company_id', r.company_id);
        set('rep_first_name', r.first_name);
        set('rep_last_name', r.last_name);
        set('rep_email', r.email);
        set('rep_phone', r.phone);
        set('rep_date_of_birth', r.date_of_birth);
        set('rep_nationality', r.nationality);
        set('rep_job_title', r.job_title);
        set('rep_ownership_percent', r.ownership_percent);
        set('rep_id_document_type', r.id_document_type);
        set('rep_id_document_expires_on', r.id_document_expires_on);
        document.getElementById('rep_id_document_number').value = '';

        ['rep_is_director', 'rep_is_owner', 'rep_is_signatory', 'rep_is_primary_contact'].forEach(field => {
            const el = document.getElementById(field);
            if (el) el.checked = !!r[field.replace('rep_', '')];
        });

        new bootstrap.Modal(document.getElementById('kt_modal_representative')).show();
    };

    window.deleteRepresentative = function (id, name) {
        if (!confirm(`Remove "${name}"? This cannot be undone.`)) return;

        fetch(`/admin/representatives/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadRepresentatives(complianceCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    document.getElementById('representativeForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('repSaveBtn');
        window.showButtonSpinner(btn);

        const id = document.getElementById('rep_id').value;
        const companyId = document.getElementById('rep_company_id').value;
        const url = id
            ? `/admin/representatives/${id}`
            : `/admin/companies/${companyId}/representatives`;

        const val = (field) => document.getElementById(field)?.value || null;
        const checked = (field) => document.getElementById(field)?.checked ? 1 : 0;

        const payload = {
            first_name: val('rep_first_name'),
            last_name: val('rep_last_name'),
            email: val('rep_email'),
            phone: val('rep_phone'),
            date_of_birth: val('rep_date_of_birth'),
            nationality: val('rep_nationality'),
            job_title: val('rep_job_title'),
            is_director: checked('rep_is_director'),
            is_owner: checked('rep_is_owner'),
            is_signatory: checked('rep_is_signatory'),
            is_primary_contact: checked('rep_is_primary_contact'),
            ownership_percent: val('rep_ownership_percent'),
            id_document_type: val('rep_id_document_type'),
            id_document_number: val('rep_id_document_number'),
            id_document_expires_on: val('rep_id_document_expires_on'),
        };

        if (id) payload._method = 'PUT';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_representative'))?.hide();
                loadRepresentatives(companyId);
            } else {
                window.showToast('error', data.message || 'Save failed');
            }
        })
        .catch(() => window.showToast('error', 'Save failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    /* ═════════════════════════════════════════════
       DOCUMENTS
       ═════════════════════════════════════════════ */

    function loadDocuments(companyId) {
        const loading = document.getElementById('comp_docs_loading');
        const empty = document.getElementById('comp_docs_empty');
        const container = document.getElementById('comp_docs_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/documents`)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('d-none');
                const docs = data.data || [];

                const countEl = document.getElementById('comp_docs_count');
                if (countEl) countEl.textContent = docs.length;

                if (!docs.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderDocuments(docs);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load documents');
            });
    }

    function renderDocuments(docs) {
        const body = document.getElementById('comp_docs_body');
        if (!body) return;
        body.innerHTML = '';

        docs.forEach(d => {
            const repHtml = d.representative
                ? h(d.representative.full_name)
                : '<span class="text-muted">—</span>';

            const tone = d.status_badge ? d.status_badge.tone : 'secondary';
            const label = d.status_badge ? d.status_badge.label : 'Unknown';

            let actions = `
                <button type="button" class="btn btn-sm btn-icon btn-light me-1" onclick="previewDocument(${d.id})" title="View" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-eye fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            `;

            if (d.status === 'pending') {
                actions += `
                    <button type="button" class="btn btn-sm btn-icon btn-light-success me-1" onclick="reviewDocument(${d.id}, 'approved')" title="Approve" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-check fs-4"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light-danger me-1" onclick="reviewDocument(${d.id}, 'rejected')" title="Reject" style="width:28px;height:28px;">
                        <i class="ki-duotone ki-cross fs-4"></i>
                    </button>
                `;
            }

            actions += `
                <button type="button" class="btn btn-sm btn-icon btn-light" onclick="deleteDocument(${d.id})" title="Delete" style="width:28px;height:28px;">
                    <i class="ki-duotone ki-trash fs-4 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                </button>
            `;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="badge badge-light-dark">${h(d.type_label)}</span></td>
                <td>
                    <div class="fw-semibold">${h(d.original_filename || '—')}</div>
                    <div class="text-muted fs-8">${h(d.size_human || '')}</div>
                </td>
                <td>${repHtml}</td>
                <td><span class="badge badge-light-${tone}">${h(label)}</span></td>
                <td class="text-muted">${h(d.created_at || '')}</td>
                <td class="text-end">${actions}</td>
            `;
            body.appendChild(tr);
        });
    }

    window.openUploadDocument = function () {
        document.getElementById('doc_company_id').value = complianceCompanyId;

        const sel = document.getElementById('doc_representative_id');
        sel.innerHTML = '<option value="">— None —</option>' +
            complianceRepresentatives.map(r => `<option value="${r.id}">${h(r.full_name)}</option>`).join('');

        document.getElementById('documentUploadForm').reset();
        new bootstrap.Modal(document.getElementById('kt_modal_upload_document')).show();
    };

    document.getElementById('documentUploadForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('docUploadBtn');
        window.showButtonSpinner(btn);

        const companyId = document.getElementById('doc_company_id').value;
        const fd = new FormData();
        fd.append('type', document.getElementById('doc_type').value);
        fd.append('company_representative_id', document.getElementById('doc_representative_id').value);
        fd.append('file', document.getElementById('doc_file').files[0]);
        if (document.getElementById('doc_expires_on').value) {
            fd.append('expires_on', document.getElementById('doc_expires_on').value);
        }

        fetch(`/admin/companies/${companyId}/documents`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_upload_document'))?.hide();
                loadDocuments(companyId);
            } else {
                window.showToast('error', data.message || 'Upload failed');
            }
        })
        .catch(() => window.showToast('error', 'Upload failed'))
        .finally(() => window.hideButtonSpinner(btn));
    });

    window.previewDocument = function (id) {
        fetch(`/admin/documents/${id}/view`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    window.showToast('error', data.message || 'File not found');
                    return;
                }

                const url = data.url;
                const isPdf = url.toLowerCase().includes('.pdf') || url.toLowerCase().includes('application%2Fpdf');

                const frame = document.getElementById('doc_preview_frame');
                const img = document.getElementById('doc_preview_image');

                if (isPdf) {
                    frame.src = url;
                    frame.style.display = '';
                    img.style.display = 'none';
                } else {
                    img.src = url;
                    img.style.display = '';
                    frame.style.display = 'none';
                    frame.src = '';
                }

                document.getElementById('doc_preview_download').href =
                    url + (url.includes('?') ? '&' : '?') + 'download=1';

                new bootstrap.Modal(document.getElementById('kt_modal_doc_preview')).show();
            })
            .catch(() => window.showToast('error', 'Failed to load document'));
    };

    window.reviewDocument = function (id, status) {
        const notes = status === 'rejected' ? prompt('Rejection reason (optional):') : null;

        fetch(`/admin/documents/${id}/review`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status, review_notes: notes })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadDocuments(complianceCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    window.deleteDocument = function (id) {
        if (!confirm('Delete this document? This cannot be undone.')) return;
        fetch(`/admin/documents/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadDocuments(complianceCompanyId);
            } else {
                window.showToast('error', data.message);
            }
        });
    };

    /* ═════════════════════════════════════════════
       VERIFICATION CHECKS
       ═════════════════════════════════════════════ */

    function loadChecks(companyId) {
        const loading = document.getElementById('comp_checks_loading');
        const empty = document.getElementById('comp_checks_empty');
        const container = document.getElementById('comp_checks_container');
        if (!loading) return;

        loading.classList.remove('d-none');
        empty.classList.add('d-none');
        container.classList.add('d-none');

        fetch(`/admin/companies/${companyId}/checks`)
            .then(res => res.json())
            .then(data => {
                loading.classList.add('d-none');
                const checks = data.data || [];

                const countEl = document.getElementById('comp_checks_count');
                if (countEl) countEl.textContent = checks.length;

                if (!checks.length) {
                    empty.classList.remove('d-none');
                    return;
                }
                container.classList.remove('d-none');
                renderChecks(checks);
            })
            .catch(() => {
                loading.classList.add('d-none');
                window.showToast('error', 'Failed to load checks');
            });
    }

    function renderChecks(checks) {
        const body = document.getElementById('comp_checks_body');
        if (!body) return;
        body.innerHTML = '';

        checks.forEach(c => {
            const tone = c.status_badge ? c.status_badge.tone : 'secondary';
            const label = c.status_badge ? c.status_badge.label : 'Unknown';

            const failureHtml = c.failure_reason
                ? `<span title="${h(c.failure_reason)}" class="text-danger fs-7">${h(c.failure_reason.substring(0, 30))}…</span>`
                : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${h(c.type_label)}</td>
                <td>${h(c.provider || '—')}</td>
                <td><span class="badge badge-light-${tone}">${h(label)}</span></td>
                <td>${c.score != null ? c.score : '—'}</td>
                <td class="text-muted">${h(c.completed_at || c.created_at || '—')}</td>
                <td class="text-end">${failureHtml}</td>
            `;
            body.appendChild(tr);
        });
    }

    window.openRunCheck = function () {
        document.getElementById('check_company_id').value = complianceCompanyId;
        new bootstrap.Modal(document.getElementById('kt_modal_run_check')).show();
    };

    document.getElementById('checkRunBtn')?.addEventListener('click', function () {
        const btn = this;
        window.showButtonSpinner(btn);

        const companyId = document.getElementById('check_company_id').value;
        const type = document.getElementById('check_type').value;

        fetch(`/admin/companies/${companyId}/checks/run`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('kt_modal_run_check'))?.hide();
                loadChecks(companyId);
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to queue check'))
        .finally(() => window.hideButtonSpinner(btn));
    });

})();
</script>