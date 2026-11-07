<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\User;
use App\Models\UserStampSignature;
use App\Support\PublicStorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserStampSignatureService
{
    public const OTHER_DOC_KEYS = ['ap4', 'ap5', 'ap7', 'ap8'];

    public const DOC_KINDS = ['v' => 'vouchers', 'i' => 'invoices', 'a' => 'agreements'];

    public function listUsers(Company $company): array
    {
        return User::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'first_name', 'last_name', 'email'])
            ->map(function (User $user) {
                $name = trim((string) ($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))));

                return [
                    'id' => $user->id,
                    'name' => $name !== '' ? $name : ('User #' . $user->id),
                    'email' => (string) ($user->email ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    public function bootstrapForCompany(Company $company): array
    {
        $users = $this->listUsers($company);
        $rows = UserStampSignature::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('user_id');

        $stamps = [];
        foreach ($users as $user) {
            $row = $rows->get($user['id']);
            $stamps[(string) $user['id']] = $this->toClient($company, $user['id'], $row, $user['name']);
        }

        return [
            'users' => $users,
            'user_stamps' => $stamps,
            'current_user_id' => Auth::id(),
        ];
    }

    public function ensure(Company $company, int $userId): UserStampSignature
    {
        $user = User::query()
            ->where('company_id', $company->id)
            ->where('id', $userId)
            ->firstOrFail();

        return UserStampSignature::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ],
            [
                'signatory_name' => trim((string) ($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')))),
                'designation' => '',
                'doc_matrix' => $this->defaultMatrix(),
                'other_docs' => ['ap4' => false, 'ap5' => false, 'ap7' => false, 'ap8' => true],
                'is_active' => true,
                'updated_by' => Auth::id(),
            ]
        );
    }

    public function save(Company $company, int $userId, array $payload): UserStampSignature
    {
        $row = $this->ensure($company, $userId);

        if (array_key_exists('signatory_name', $payload)) {
            $row->signatory_name = trim((string) $payload['signatory_name']);
        }
        if (array_key_exists('designation', $payload)) {
            $row->designation = trim((string) $payload['designation']);
        }
        if (array_key_exists('doc_matrix', $payload) && is_array($payload['doc_matrix'])) {
            $row->doc_matrix = $payload['doc_matrix'];
        }
        if (array_key_exists('other_docs', $payload) && is_array($payload['other_docs'])) {
            $other = (array) ($row->other_docs ?: []);
            foreach (self::OTHER_DOC_KEYS as $key) {
                if (array_key_exists($key, $payload['other_docs'])) {
                    $other[$key] = (bool) $payload['other_docs'][$key];
                }
            }
            $row->other_docs = $other;
        }
        if (array_key_exists('is_active', $payload)) {
            $row->is_active = (bool) $payload['is_active'];
        }

        foreach (['stamp_path' => 'stamp_path', 'signature_path' => 'signature_path', 'stampimg' => 'stamp_path', 'signimg' => 'signature_path'] as $in => $col) {
            if (! array_key_exists($in, $payload)) {
                continue;
            }
            $value = $payload[$in];
            if (! is_string($value)) {
                continue;
            }
            if ($value === '' || Str::startsWith($value, ['http://', 'https://', 'data:', 'blob:'])) {
                // Keep existing stored path when UI sends a preview URL or clear request via dedicated clear.
                if ($value === '') {
                    $this->deleteFile($row->{$col});
                    $row->{$col} = null;
                }
                continue;
            }
            $row->{$col} = PublicStorageDisk::normalize($value) ?: $value;
        }

        $row->updated_by = Auth::id();
        $row->save();

        return $row->fresh();
    }

    public function uploadAsset(Company $company, int $userId, string $field, UploadedFile $file): array
    {
        $field = $field === 'signimg' ? 'signature_path' : ($field === 'stampimg' ? 'stamp_path' : $field);
        if (! in_array($field, ['stamp_path', 'signature_path'], true)) {
            abort(422, 'Invalid stamp field.');
        }

        $row = $this->ensure($company, $userId);
        $dir = 'user-stamps/' . $company->id . '/' . $userId;
        $path = PublicStorageDisk::storeUploadedFile($file, $dir);

        $this->deleteFile($row->{$field});
        $row->{$field} = $path;
        $row->updated_by = Auth::id();
        $row->save();

        return [
            'field' => $field === 'stamp_path' ? 'stampimg' : 'signimg',
            'path' => $path,
            'url' => $this->publicUrl($path),
            'record' => $this->toClient($company, $userId, $row->fresh()),
        ];
    }

    public function forUser(Company $company, int $userId): ?array
    {
        $row = UserStampSignature::query()
            ->where('company_id', $company->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return null;
        }

        return $this->toClient($company, $userId, $row);
    }

    public function forAuthUser(?Company $company = null): ?array
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }
        $company = $company ?: Company::query()->find((int) $user->company_id);
        if (! $company) {
            return null;
        }

        return $this->forUser($company, (int) $user->id);
    }

    public function canApply(?array $stamp, string $module, string $kind): bool
    {
        if (! $stamp || empty($stamp['is_active'])) {
            return false;
        }
        $kind = strtolower($kind);
        if (isset(self::DOC_KINDS[$kind])) {
            $kindKey = $kind;
        } else {
            $map = array_flip(self::DOC_KINDS);
            $kindKey = $map[$kind] ?? null;
        }
        if (! $kindKey) {
            // Other document tiles (salary slips, letters, etc.)
            return (bool) data_get($stamp, 'other_docs.' . $module, false);
        }

        return (bool) data_get($stamp, 'doc_matrix.' . $module . '.' . $kindKey, false);
    }

    public function toClient(Company $company, int $userId, ?UserStampSignature $row = null, ?string $fallbackName = null): array
    {
        $row = $row ?: UserStampSignature::query()
            ->where('company_id', $company->id)
            ->where('user_id', $userId)
            ->first();

        $other = (array) ($row?->other_docs ?: ['ap4' => false, 'ap5' => false, 'ap7' => false, 'ap8' => true]);

        return [
            'user_id' => $userId,
            'stampimg' => $this->publicUrl($row?->stamp_path),
            'stampimg_path' => $row?->stamp_path,
            'signimg' => $this->publicUrl($row?->signature_path),
            'signimg_path' => $row?->signature_path,
            'signame' => (string) ($row?->signatory_name ?: $fallbackName ?: ''),
            'sigdes' => (string) ($row?->designation ?: ''),
            'apm' => is_array($row?->doc_matrix) && $row->doc_matrix ? $row->doc_matrix : $this->defaultMatrix(),
            'ap4' => (bool) ($other['ap4'] ?? false),
            'ap5' => (bool) ($other['ap5'] ?? false),
            'ap7' => (bool) ($other['ap7'] ?? false),
            'ap8' => (bool) ($other['ap8'] ?? true),
            'is_active' => (bool) ($row?->is_active ?? true),
            'has_record' => (bool) $row,
        ];
    }

    public function defaultMatrix(): array
    {
        $on = [
            'v' => ['Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'Supplier', 'Garages', 'Leasing Companies'],
            'i' => ['Customers', 'Accounts', 'VAT', 'Vehicle on rent'],
            'a' => ['Agreements', 'Riders', 'Employees', 'Leasing Companies', 'Vehicle on rent', 'Customers', 'Supplier'],
        ];
        $groups = [
            ['Finance', ['Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'VAT']],
            ['People', ['Employees', 'Riders', 'Recruiters', 'Customers']],
            ['Fleet', ['Bikes', 'Vehicle on rent', 'Fuel Cards', 'Sims', 'RTA Fines', 'RTA Saliks', 'Garages', 'Leasing Companies']],
            ['Legal & Admin', ['Agreements', 'Legal Case', 'Visa Expense', 'License Expense', 'Passport Handover', 'Documents']],
            ['Purchasing', ['Supplier', 'Items', 'Assets']],
        ];
        $m = [];
        foreach ($groups as [, $mods]) {
            foreach ($mods as $x) {
                $m[$x] = [
                    'v' => in_array($x, $on['v'], true),
                    'i' => in_array($x, $on['i'], true),
                    'a' => in_array($x, $on['a'], true),
                ];
            }
        }

        return $m;
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://', 'data:', 'blob:'])) {
            return $path;
        }
        $normalized = PublicStorageDisk::normalize($path);
        if ($normalized === '') {
            return null;
        }
        if (PublicStorageDisk::isCloud()) {
            return PublicStorageDisk::url($normalized);
        }
        $base = rtrim((string) request()->getBasePath(), '/');

        return $base . '/storage/' . $normalized;
    }

    protected function deleteFile(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://', 'data:', 'blob:'])) {
            return;
        }
        PublicStorageDisk::delete($path);
    }
}
