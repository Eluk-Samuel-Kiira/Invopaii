<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Company\CompanyBankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function getBankAccounts(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $accounts = $company->bankAccounts()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($a) => $this->format($a));

            return response()->json(['success' => true, 'data' => $accounts]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeBankAccount(Request $request, $companyId)
    {
        $data = $this->validated($request);

        try {
            $company = Company::findOrFail($companyId);

            // Duplicate fingerprint check
            if (!empty($data['account_number'])) {
                $fp = hash_hmac('sha256', preg_replace('/\s+/', '', $data['account_number']), config('app.key'));
                if ($company->bankAccounts()->where('fingerprint', $fp)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This account already exists for this company.',
                    ], 422);
                }
            }

            $account = $company->bankAccounts()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Bank account added.',
                'data' => $this->format($account),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateBankAccount(Request $request, $id)
    {
        try {
            $account = CompanyBankAccount::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Account not found'], 404);
        }

        $data = $this->validated($request, $account->id);

        try {
            // Blank password-style: don't overwrite account_number if empty
            if (empty($data['account_number'])) unset($data['account_number']);
            if (empty($data['iban'])) unset($data['iban']);
            if (empty($data['msisdn'])) unset($data['msisdn']);

            $account->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Bank account updated.',
                'data' => $this->format($account->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteBankAccount($id)
    {
        try {
            $account = CompanyBankAccount::findOrFail($id);
            $account->delete();

            return response()->json(['success' => true, 'message' => 'Bank account removed.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /**
     * Make this account the default for its currency.
     */
    public function setDefault($id)
    {
        try {
            $account = CompanyBankAccount::findOrFail($id);
            $account->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Default account updated.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to set default'], 500);
        }
    }

    /**
     * Change status (verify / disable / mark errored).
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(['new', 'validated', 'verified', 'errored', 'disabled'])],
            'failure_reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $account = CompanyBankAccount::findOrFail($id);
            $account->status = $request->status;

            if ($request->status === 'verified') {
                $account->verified_at = now();
                $account->failure_reason = null;
            } elseif ($request->status === 'errored') {
                $account->failure_reason = $request->failure_reason;
            }

            $account->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated.',
                'data' => $this->format($account->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }

    /* ---------- Helpers ---------- */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['bank_account', 'mobile_money', 'wallet'])],
            'currency' => ['required', 'string', 'size:3'],
            'country_code' => ['required', 'string', 'size:2'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_holder_type' => ['required', Rule::in(['individual', 'company'])],

            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:32'],
            'branch_code' => ['nullable', 'string', 'max:32'],
            'account_number' => ['nullable', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'swift_bic' => ['nullable', 'string', 'max:16'],
            'routing_number' => ['nullable', 'string', 'max:32'],
            'sort_code' => ['nullable', 'string', 'max:16'],

            'mobile_network' => ['nullable', 'string', 'max:32'],
            'msisdn' => ['nullable', 'string', 'max:32'],

            'is_default' => ['boolean'],
        ]);

        $validated['currency'] = strtoupper($validated['currency']);
        $validated['country_code'] = strtoupper($validated['country_code']);
        $validated['is_default'] = (bool) ($validated['is_default'] ?? false);

        return $validated;
    }

    protected function format(CompanyBankAccount $a): array
    {
        return [
            'id' => $a->id,
            'uuid' => $a->uuid,
            'public_id' => $a->public_id,
            'company_id' => $a->company_id,
            'type' => $a->type,
            'type_label' => $a->type_label,
            'currency' => $a->currency,
            'country_code' => $a->country_code,
            'account_holder_name' => $a->account_holder_name,
            'account_holder_type' => $a->account_holder_type,
            'bank_name' => $a->bank_name,
            'bank_code' => $a->bank_code,
            'branch_code' => $a->branch_code,
            'swift_bic' => $a->swift_bic,
            'routing_number' => $a->routing_number,
            'sort_code' => $a->sort_code,
            'mobile_network' => $a->mobile_network,
            'last_four' => $a->last_four,
            'masked_account' => $a->masked_account,
            'display_label' => $a->display_label,
            'is_default' => (bool) $a->is_default,
            'status' => $a->status,
            'status_badge' => $a->status_badge,
            'verification_method' => $a->verification_method,
            'verified_at' => $a->verified_at?->format('M d, Y H:i'),
            'failure_reason' => $a->failure_reason,
            'created_at' => $a->created_at?->format('M d, Y'),
        ];
    }
}