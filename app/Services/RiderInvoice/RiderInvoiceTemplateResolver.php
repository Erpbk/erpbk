<?php

namespace App\Services\RiderInvoice;

use App\Models\RiderInvoiceTemplate;
use App\Models\RiderInvoices;
use App\Support\CompanyContext;
use Illuminate\Support\Collection;

class RiderInvoiceTemplateResolver
{
    public function isEnabled(): bool
    {
        return RiderInvoiceTemplate::isSchemaReady();
    }

    public function activeTemplates(): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        return RiderInvoiceTemplate::query()
            ->where('status', true)
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('template_name')
            ->get();
    }

    public function resolveForInvoice(RiderInvoices $invoice): ?RiderInvoiceTemplate
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if ($invoice->template_id) {
            $template = RiderInvoiceTemplate::query()
                ->where('id', $invoice->template_id)
                ->where('status', true)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return $this->defaultTemplate();
    }

    public function defaultTemplate(): ?RiderInvoiceTemplate
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $template = RiderInvoiceTemplate::query()
            ->where('is_default', true)
            ->where('status', true)
            ->first();

        if ($template) {
            return $template;
        }

        $existing = RiderInvoiceTemplate::query()->orderBy('id')->first();
        if ($existing) {
            if (! $existing->is_default) {
                $existing->setAsDefault();
            }

            return $existing->fresh();
        }

        return RiderInvoiceTemplate::create([
            'company_id' => CompanyContext::id(),
            'template_name' => 'Modern (Default)',
            'layout_key' => RiderInvoiceTemplate::LAYOUT_MODERN,
            'description' => 'Default rider invoice layout with card-style sections.',
            'is_default' => true,
            'status' => true,
            'display_order' => 1,
        ]);
    }

    public function resolveViewForInvoice(RiderInvoices $invoice): string
    {
        $template = $this->resolveForInvoice($invoice);

        return $template?->viewName() ?? RiderInvoiceTemplate::FALLBACK_VIEW;
    }

    public function viewForInvoice(RiderInvoices $invoice): string
    {
        return $this->resolveViewForInvoice($invoice);
    }
}
