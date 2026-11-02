<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\visa_installment_plan;
use App\Models\license_installment_plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutoMarkInstallments
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only run auto-marking on visa expense related routes and if user is authenticated.
        // auth()->check() can throw QueryException (e.g. wrong/missing DB in .env) — do not break the request.
        try {
            $authenticated = auth()->check();
        } catch (QueryException $e) {
            Log::warning('AutoMarkInstallments: skipped (database unavailable for auth)', [
                'message' => $e->getMessage(),
            ]);
            return $next($request);
        }

        if ($authenticated && $this->shouldAutoMark($request)) {
            try {
                $this->autoMarkOverdueInstallments();
            } catch (\Exception $e) {
                Log::error('AutoMarkInstallments middleware failed: ' . $e->getMessage());
            }
        }

        return $next($request);
    }

    /**
     * Check if auto-marking should run for this request
     */
    private function shouldAutoMark(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
        }

        $routeName = $route->getName();

        // Run on Visa Expense, License Expense, and Installments routes
        return $routeName && (
            str_contains($routeName, 'VisaExpense')
            || str_contains($routeName, 'LicenseExpense')
            || str_contains($routeName, 'Installments')
        );
    }

    /**
     * Auto-mark overdue installments silently
     */
    private function autoMarkOverdueInstallments(): void
    {
        try {
            $today = Carbon::today()->format('Y-m-d');

            $overdueInstallments = visa_installment_plan::where('status', visa_installment_plan::STATUS_PENDING)
                ->where('date', '<=', $today)
                ->get();

            if (Schema::hasTable('license_installment_plans')) {
                $overdueInstallments = $overdueInstallments->concat(
                    license_installment_plan::where('status', license_installment_plan::STATUS_PENDING)
                        ->where('date', '<=', $today)
                        ->get()
                );
            }

            if ($overdueInstallments->isEmpty()) {
                return;
            }

            $updatedCount = 0;

            foreach ($overdueInstallments as $installment) {
                try {
                    DB::beginTransaction();

                    // Mark installment as paid
                    $installment->status = visa_installment_plan::STATUS_PAID;
                    $installment->updated_by = auth()->user()->id ?? 1;
                    $installment->save();

                    DB::commit();
                    $updatedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Middleware auto-mark error: ' . $e->getMessage(), [
                        'installment_id' => $installment->id
                    ]);
                }
            }

            if ($updatedCount > 0) {
                Log::info("Middleware auto-marked {$updatedCount} overdue installments");
            }
        } catch (\Exception $e) {
            Log::error('Auto-mark middleware error: ' . $e->getMessage());
        }
    }
}
