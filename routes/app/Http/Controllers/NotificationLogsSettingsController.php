<?php

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notifications\NotificationTypeRegistry;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationLogsSettingsController extends Controller
{
    public function index(
        Request $request,
        string $company_slug,
        NotificationTypeRegistry $registry
    ) {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        $validated = $request->validate([
            'channel' => ['nullable', 'string', Rule::in(['in_app', 'email'])],
            'status' => ['nullable', 'string', Rule::in([
                NotificationDelivery::STATUS_PENDING,
                NotificationDelivery::STATUS_SENT,
                NotificationDelivery::STATUS_FAILED,
                NotificationDelivery::STATUS_SKIPPED,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = NotificationDelivery::query()
            ->where('company_id', $companyId)
            ->with([
                'notification:id,user_id,type,title,body,severity,created_at,action_url,rule_id,source_type,source_id',
                'notification.user:id,name,email,username,first_name,last_name',
            ])
            ->orderByDesc('id');

        if (! empty($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['q'])) {
            $q = trim($validated['q']);
            $query->where(function ($builder) use ($q) {
                $builder->where('to_email', 'like', '%'.$q.'%')
                    ->orWhereHas('notification', function ($n) use ($q) {
                        $n->where('title', 'like', '%'.$q.'%')
                            ->orWhere('type', 'like', '%'.$q.'%')
                            ->orWhere('body', 'like', '%'.$q.'%');
                    });
            });
        }

        $logs = $query->paginate(40)->withQueryString();

        $statusCounts = NotificationDelivery::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $typeLabels = [];
        foreach ($registry->all() as $typeKey => $def) {
            $typeLabels[$typeKey] = (string) ($def['label'] ?? $typeKey);
        }

        return view('settings.notification_logs.index', [
            'logs' => $logs,
            'filters' => [
                'channel' => $validated['channel'] ?? '',
                'status' => $validated['status'] ?? '',
                'q' => $validated['q'] ?? '',
            ],
            'statusCounts' => $statusCounts,
            'typeLabels' => $typeLabels,
            'channelLabels' => [
                'in_app' => __('In-App'),
                'email' => __('Email'),
            ],
            'statusLabels' => [
                NotificationDelivery::STATUS_PENDING => __('Pending'),
                NotificationDelivery::STATUS_SENT => __('Sent'),
                NotificationDelivery::STATUS_FAILED => __('Failed'),
                NotificationDelivery::STATUS_SKIPPED => __('Skipped'),
            ],
        ]);
    }

    public static function recipientLabel(?NotificationDelivery $delivery): string
    {
        if (! $delivery) {
            return '—';
        }

        if (! empty($delivery->to_email)) {
            return (string) $delivery->to_email;
        }

        $user = $delivery->notification?->user;
        if (! $user instanceof User) {
            return '—';
        }

        $name = trim((string) ($user->name ?: ''));
        if ($name === '') {
            $name = trim(trim((string) ($user->first_name ?? '')).' '.trim((string) ($user->last_name ?? '')));
        }
        if ($name === '') {
            $name = $user->username ?: ('#'.$user->id);
        }

        $email = trim((string) ($user->email ?? ''));
        if ($email !== '') {
            return $email.' ('.$name.')';
        }

        return $name;
    }
}
