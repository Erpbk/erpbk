<?php

namespace App\Support;

use App\Models\Cheques;
use App\Models\Payment;
use App\Models\Receipt;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cheque-linked payment/receipt edits are only allowed from the Cheques module.
 *
 * Requires all of:
 * - from_cheques=1 (UI flag)
 * - cash_&_banks_cheques_edit permission
 * - request origin/context from cheques (referer / previous URL / recent module session)
 * - for updates: a session edit token issued when the edit form was opened
 */
class ChequeClearEditGuard
{
    public const PERMISSION = 'cash_&_banks_cheques_edit';

    private const SESSION_MODULE_AT = 'cheque_module_context_at';

    private const SESSION_TOKEN_PREFIX = 'cheque_clear_edit.';

    private const MODULE_CONTEXT_TTL_SECONDS = 7200;

    private const EDIT_TOKEN_TTL_SECONDS = 3600;

    public static function markChequesModuleContext(): void
    {
        session([self::SESSION_MODULE_AT => now()->getTimestamp()]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|null
     */
    public static function denyIfNeeded(Payment|Receipt $doc, Request $request, string $entityPluralLabel)
    {
        if (! Cheques::linkedIdForDocument($doc)) {
            return null;
        }

        if (static::allows($doc, $request)) {
            if (static::isEditOpenRequest($request)) {
                static::issueEditToken($doc);
            }

            return null;
        }

        $message = "{$entityPluralLabel} linked to cheques can only be changed from the Cheques module.";

        if ($request->ajax()) {
            return response()->json(['message' => $message], 422);
        }

        Flash::error($message);

        return redirect()->back();
    }

    public static function allows(Payment|Receipt $doc, Request $request): bool
    {
        if (! $request->boolean('from_cheques')) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $user->can(self::PERMISSION)) {
            return false;
        }

        if (! static::requestComesFromChequesModule($request)) {
            return false;
        }

        if (static::isUpdateRequest($request)) {
            return static::hasValidEditToken($doc, $request);
        }

        return true;
    }

    public static function editTokenFor(Payment|Receipt $doc): ?string
    {
        $payload = session(static::sessionTokenKey($doc));
        if (! is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < now()->getTimestamp()) {
            return null;
        }

        $token = $payload['token'] ?? null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function issueEditToken(Payment|Receipt $doc): string
    {
        $token = bin2hex(random_bytes(20));
        session([
            static::sessionTokenKey($doc) => [
                'token' => $token,
                'cheque_id' => Cheques::linkedIdForDocument($doc),
                'expires_at' => now()->getTimestamp() + self::EDIT_TOKEN_TTL_SECONDS,
            ],
        ]);

        return $token;
    }

    public static function requestComesFromChequesModule(Request $request): bool
    {
        foreach (array_filter([
            $request->headers->get('referer'),
            $request->headers->get('referrer'),
            url()->previous(),
        ]) as $url) {
            if (static::urlIsChequesModule((string) $url)) {
                return true;
            }
        }

        return static::hasRecentChequesModuleContext();
    }

    public static function urlIsChequesModule(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }

        $path = '/' . trim($path, '/');

        // /cheques..., /{company}/cheques..., /banks/cheques/{id}, /{company}/banks/cheques/{id}
        return (bool) preg_match('#/(?:[^/]+/)?(?:banks/)?cheques(?:/|$)#i', $path);
    }

    private static function hasRecentChequesModuleContext(): bool
    {
        $at = (int) session(self::SESSION_MODULE_AT, 0);
        if ($at <= 0) {
            return false;
        }

        return (now()->getTimestamp() - $at) <= self::MODULE_CONTEXT_TTL_SECONDS;
    }

    private static function hasValidEditToken(Payment|Receipt $doc, Request $request): bool
    {
        $provided = (string) $request->input('cheque_clear_edit_token', '');
        if ($provided === '') {
            return false;
        }

        $payload = session(static::sessionTokenKey($doc));
        if (! is_array($payload)) {
            return false;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < now()->getTimestamp()) {
            return false;
        }

        $expected = (string) ($payload['token'] ?? '');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return false;
        }

        $sessionChequeId = (int) ($payload['cheque_id'] ?? 0);
        $docChequeId = (int) (Cheques::linkedIdForDocument($doc) ?? 0);

        return $sessionChequeId > 0 && $sessionChequeId === $docChequeId;
    }

    private static function sessionTokenKey(Payment|Receipt $doc): string
    {
        $type = $doc instanceof Payment ? 'payment' : 'receipt';

        return self::SESSION_TOKEN_PREFIX . $type . '.' . $doc->getKey();
    }

    private static function isUpdateRequest(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['PUT', 'PATCH', 'POST'], true)
            && ! static::isEditOpenRequest($request);
    }

    private static function isEditOpenRequest(Request $request): bool
    {
        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        $routeName = (string) optional($request->route())->getName();

        return str_ends_with($routeName, '.edit')
            || str_contains($routeName, 'payments.edit')
            || str_contains($routeName, 'receipts.edit');
    }
}
