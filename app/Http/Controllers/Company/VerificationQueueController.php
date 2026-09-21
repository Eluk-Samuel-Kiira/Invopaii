<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Company\CompanyDocument;
use App\Models\Company\CompanyRepresentative;
use Illuminate\Http\Request;

class VerificationQueueController extends Controller
{
    public function index()
    {
        return view('company.compliance.index');
    }

    /**
     * Paginated list of companies awaiting verification.
     */
    public function getQueue(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $status = $request->get('status', 'pending');  // pending | in_review | all

        $query = Company::query()
            ->with(['country:id,name,iso2,flag_emoji', 'owner:id,name,email'])
            ->search($search)
            ->withCount([
                'representatives',
                'documents',
                'documents as pending_documents_count' => fn ($q) => $q->where('status', 'pending'),
                'documents as approved_documents_count' => fn ($q) => $q->where('status', 'approved'),
            ]);

        if ($status === 'pending') {
            $query->whereIn('kyb_status', ['unverified', 'pending']);
        } elseif ($status === 'in_review') {
            $query->where('status', 'in_review');
        }
        // 'all' → no filter on kyb_status

        $companies = $query->orderByRaw("FIELD(kyb_status, 'pending', 'unverified', 'rejected', 'verified')")
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $companies->currentPage(),
            'data' => collect($companies->items())->map(fn ($c) => $this->formatQueueRow($c))->toArray(),
            'first_page_url' => $companies->url(1),
            'from' => $companies->firstItem(),
            'last_page' => $companies->lastPage(),
            'last_page_url' => $companies->url($companies->lastPage()),
            'next_page_url' => $companies->nextPageUrl(),
            'prev_page_url' => $companies->previousPageUrl(),
            'to' => $companies->lastItem(),
            'total' => $companies->total(),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Dashboard-style counts for the queue page header.
     */
    public function getStats()
    {
        return response()->json([
            'pending_verification'    => Company::where('kyb_status', 'pending')->count(),
            'unverified'              => Company::where('kyb_status', 'unverified')->count(),
            'verified'                => Company::where('kyb_status', 'verified')->count(),
            'rejected'                => Company::where('kyb_status', 'rejected')->count(),
            'pending_documents'       => CompanyDocument::where('status', 'pending')->count(),
            'pending_review_companies'=> Company::where('status', 'in_review')->count(),
        ]);
    }

    protected function formatQueueRow(Company $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'public_id' => $c->public_id,
            'slug' => $c->slug,
            'brand_color' => $c->brand_color,
            'country' => $c->country ? [
                'name' => $c->country->name,
                'iso2' => $c->country->iso2,
                'flag_emoji' => $c->country->flag_emoji,
            ] : null,
            'owner' => $c->owner ? [
                'name' => $c->owner->name,
                'email' => $c->owner->email,
            ] : null,
            'status' => $c->status,
            'status_badge' => $c->status_badge,
            'kyb_status' => $c->kyb_status,
            'kyb_badge' => $c->kyb_badge,
            'representatives_count' => $c->representatives_count,
            'documents_count' => $c->documents_count,
            'pending_documents_count' => $c->pending_documents_count,
            'approved_documents_count' => $c->approved_documents_count,
            'created_at' => $c->created_at?->format('M d, Y'),
            'age_days' => $c->created_at?->diffInDays(now()),
        ];
    }
}