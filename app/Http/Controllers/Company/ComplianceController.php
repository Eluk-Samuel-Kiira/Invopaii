<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Company\CompanyDocument;
use App\Models\Company\CompanyRepresentative;
use App\Models\Company\VerificationCheck;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComplianceController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       REPRESENTATIVES
       ═══════════════════════════════════════════════════════ */

    public function getRepresentatives(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $reps = $company->representatives()
                ->orderByDesc('is_primary_contact')
                ->orderBy('last_name')
                ->get()
                ->map(fn ($r) => $this->formatRepresentative($r));

            return response()->json(['success' => true, 'data' => $reps]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeRepresentative(Request $request, $companyId)
    {
        $data = $this->validatedRepresentative($request);

        try {
            $company = Company::findOrFail($companyId);
            $rep = $company->representatives()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Representative added.',
                'data' => $this->formatRepresentative($rep),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateRepresentative(Request $request, $id)
    {
        try {
            $rep = CompanyRepresentative::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Representative not found'], 404);
        }

        $data = $this->validatedRepresentative($request, $rep->id);

        try {
            $rep->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Representative updated.',
                'data' => $this->formatRepresentative($rep->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteRepresentative($id)
    {
        try {
            $rep = CompanyRepresentative::findOrFail($id);
            $rep->delete();

            return response()->json(['success' => true, 'message' => 'Representative removed.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       DOCUMENTS
       ═══════════════════════════════════════════════════════ */

    public function getDocuments(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $docs = $company->documents()
                ->with('representative:id,first_name,last_name')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($d) => $this->formatDocument($d));

            return response()->json(['success' => true, 'data' => $docs]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function uploadDocument(Request $request, $companyId)
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in([
                'certificate_of_incorporation', 'tax_certificate', 'bank_statement',
                'utility_bill', 'id_front', 'id_back', 'selfie', 'memarts',
            ])],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'company_representative_id' => ['nullable', 'integer', 'exists:company_representatives,id'],
            'expires_on' => ['nullable', 'date', 'after:today'],
        ]);

        try {
            $company = Company::findOrFail($companyId);
            $file = $request->file('file');

            $path = $file->store("companies/{$company->uuid}/documents", 's3');

            $doc = $company->documents()->create([
                'company_representative_id' => $request->company_representative_id,
                'type' => $request->type,
                'original_filename' => $file->getClientOriginalName(),
                'disk' => 's3',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'status' => 'pending',
                'expires_on' => $request->expires_on,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded.',
                'data' => $this->formatDocument($doc),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Stream a document inline (view in browser) or as download.
     */
    public function viewDocument(Request $request, $id)
    {
        try {
            $doc = CompanyDocument::findOrFail($id);

            if (!Storage::disk($doc->disk)->exists($doc->path)) {
                return response()->json(['success' => false, 'message' => 'File missing'], 404);
            }

            $download = $request->boolean('download');

            // Signed temporary URL for S3, streamed content for local
            if ($doc->disk === 's3') {
                $url = Storage::disk('s3')->temporaryUrl(
                    $doc->path,
                    now()->addMinutes(5),
                    $download ? ['ResponseContentDisposition' => 'attachment; filename="' . $doc->original_filename . '"'] : []
                );
                return response()->json(['success' => true, 'url' => $url]);
            }

            return Storage::disk($doc->disk)->download($doc->path, $doc->original_filename);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }
    }

    public function reviewDocument(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $doc = CompanyDocument::findOrFail($id);

            $doc->update([
                'status' => $request->status,
                'reviewed_by_id' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $request->review_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Document {$request->status}.",
                'data' => $this->formatDocument($doc->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to review'], 500);
        }
    }

    public function deleteDocument($id)
    {
        try {
            $doc = CompanyDocument::findOrFail($id);
            Storage::disk($doc->disk)->delete($doc->path);
            $doc->delete();

            return response()->json(['success' => true, 'message' => 'Document deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       VERIFICATION CHECKS
       ═══════════════════════════════════════════════════════ */

    public function getVerificationChecks(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $checks = $company->verificationChecks()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($c) => $this->formatCheck($c));

            return response()->json(['success' => true, 'data' => $checks]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function runVerificationCheck(Request $request, $companyId)
    {
        $request->validate([
            'type' => ['required', Rule::in([
                'kyb_registry', 'aml_screening', 'sanctions', 'pep',
                'document_ocr', 'liveness', 'bank_account',
            ])],
        ]);

        try {
            $company = Company::findOrFail($companyId);

            // Stub: in reality you'd dispatch a job here that calls the provider.
            $check = $company->verificationChecks()->create([
                'type' => $request->type,
                'provider' => 'internal',
                'status' => 'pending',
                'request_payload' => ['company_id' => $company->id, 'triggered_by' => auth()->id()],
            ]);

            // dispatch(new RunVerificationCheckJob($check));

            return response()->json([
                'success' => true,
                'message' => 'Check queued.',
                'data' => $this->formatCheck($check),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to queue check'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validatedRepresentative(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'job_title' => ['nullable', 'string', 'max:255'],

            'is_director' => ['boolean'],
            'is_owner' => ['boolean'],
            'is_signatory' => ['boolean'],
            'is_primary_contact' => ['boolean'],
            'ownership_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'id_document_type' => ['nullable', Rule::in(['passport', 'national_id', 'drivers_license'])],
            'id_document_number' => ['nullable', 'string', 'max:64'],
            'id_document_country' => ['nullable', 'string', 'size:2'],
            'id_document_expires_on' => ['nullable', 'date'],

            'address_line1' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ]);
    }

    protected function formatRepresentative(CompanyRepresentative $r): array
    {
        return [
            'id' => $r->id,
            'uuid' => $r->uuid,
            'company_id' => $r->company_id,
            'full_name' => $r->full_name,
            'first_name' => $r->first_name,
            'last_name' => $r->last_name,
            'email' => $r->email,
            'phone' => $r->phone,
            'date_of_birth' => $r->date_of_birth?->toDateString(),
            'nationality' => $r->nationality,
            'job_title' => $r->job_title,
            'is_director' => (bool) $r->is_director,
            'is_owner' => (bool) $r->is_owner,
            'is_signatory' => (bool) $r->is_signatory,
            'is_primary_contact' => (bool) $r->is_primary_contact,
            'ownership_percent' => $r->ownership_percent ? (string) $r->ownership_percent : null,
            'roles' => $r->roles,
            'id_document_type' => $r->id_document_type,
            'id_document_masked' => $r->masked_document_number,
            'id_document_country' => $r->id_document_country,
            'id_document_expires_on' => $r->id_document_expires_on?->toDateString(),
            'address_line1' => $r->address_line1,
            'city' => $r->city,
            'state' => $r->state,
            'postal_code' => $r->postal_code,
            'country_code' => $r->country_code,
            'kyc_status' => $r->kyc_status,
            'kyc_badge' => $r->kyc_badge,
            'pep_check_passed' => $r->pep_check_passed,
            'sanctions_check_passed' => $r->sanctions_check_passed,
            'verified_at' => $r->verified_at?->format('M d, Y H:i'),
            'rejection_reason' => $r->rejection_reason,
            'created_at' => $r->created_at?->format('M d, Y'),
        ];
    }

    protected function formatDocument(CompanyDocument $d): array
    {
        return [
            'id' => $d->id,
            'uuid' => $d->uuid,
            'company_id' => $d->company_id,
            'company_representative_id' => $d->company_representative_id,
            'representative' => $d->representative ? [
                'id' => $d->representative->id,
                'full_name' => $d->representative->full_name,
            ] : null,
            'type' => $d->type,
            'type_label' => $d->type_label,
            'original_filename' => $d->original_filename,
            'mime_type' => $d->mime_type,
            'size_bytes' => $d->size_bytes,
            'size_human' => $d->size_bytes ? number_format($d->size_bytes / 1024, 1) . ' KB' : null,
            'status' => $d->status,
            'status_badge' => $d->status_badge,
            'is_previewable' => $d->is_previewable,
            'reviewed_by' => $d->reviewedBy?->name,
            'reviewed_at' => $d->reviewed_at?->format('M d, Y H:i'),
            'review_notes' => $d->review_notes,
            'expires_on' => $d->expires_on?->toDateString(),
            'created_at' => $d->created_at?->format('M d, Y'),
        ];
    }

    protected function formatCheck(VerificationCheck $c): array
    {
        return [
            'id' => $c->id,
            'uuid' => $c->uuid,
            'type' => $c->type,
            'type_label' => $c->type_label,
            'provider' => $c->provider,
            'provider_reference' => $c->provider_reference,
            'status' => $c->status,
            'status_badge' => $c->status_badge,
            'score' => $c->score,
            'failure_reason' => $c->failure_reason,
            'completed_at' => $c->completed_at?->format('M d, Y H:i'),
            'created_at' => $c->created_at?->format('M d, Y H:i'),
        ];
    }
}