<?php

namespace App\Services\Email;

use App\Models\Company;
use App\Models\Settings;
use App\Support\CompanyContext;
use App\Support\PublicStorageDisk;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class CompanyEmailBrandingService
{
  private const SETTING_KEYS = [
    'company_name',
    'company_logo',
    'company_address',
    'company_phone',
    'company_email',
    'company_city',
    'company_country',
  ];

  /**
   * Branding payload for company-scoped transactional emails.
   *
   * @return array<string, mixed>
   */
  public function resolve(?int $companyId = null): array
  {
    $company = $this->resolveCompany($companyId);
    if (!$company) {
      return $this->neutralBranding();
    }

    $stored = Settings::query()
      ->where('company_id', $company->id)
      ->whereIn('name', self::SETTING_KEYS)
      ->pluck('value', 'name')
      ->all();

    $brandingJson = $this->decodeBrandingJson($company->branding_json);

    $name = trim((string) ($company->name ?: ($stored['company_name'] ?? '')));
    $logoPath = $company->logo ?: ($stored['company_logo'] ?? null);
    $primaryColor = $this->normalizeColor(
      $brandingJson['email_primary_color'] ?? $company->primary_color ?? null,
      '#2563eb'
    );
    $secondaryColor = $this->normalizeColor(
      $brandingJson['email_secondary_color'] ?? $company->secondary_color ?? null,
      '#1e3a8a'
    );

    $address = trim((string) ($company->address ?: ($stored['company_address'] ?? '')));
    $phone = trim((string) ($company->phone ?: ($stored['company_phone'] ?? '')));
    $email = trim((string) ($company->email ?: ($stored['company_email'] ?? '')));
    $city = trim((string) ($company->city ?: ($stored['company_city'] ?? '')));
    $country = trim((string) ($company->country ?: ($stored['company_country'] ?? '')));

    $footerLines = array_values(array_filter([
      $address !== '' ? $address : null,
      ($city !== '' || $country !== '') ? trim($city . ($city !== '' && $country !== '' ? ', ' : '') . $country) : null,
      $phone !== '' ? 'Tel: ' . $phone : null,
    ]));

    $customFooter = trim((string) ($brandingJson['email_footer_text'] ?? ''));
    if ($customFooter !== '') {
      $footerLines[] = $customFooter;
    }

    return [
      'company_id' => (int) $company->id,
      'name' => $name !== '' ? $name : config('app.name'),
      'logo_url' => $this->resolveLogoUrl($logoPath),
      'primary_color' => $primaryColor,
      'secondary_color' => $secondaryColor,
      'address' => $address,
      'phone' => $phone,
      'email' => $email,
      'city' => $city,
      'country' => $country,
      'footer_lines' => $footerLines,
      'app_url' => URL::to('/app/' . $company->slug),
    ];
  }

  /**
   * Merge branding into Mail::send view data.
   *
   * @param  array<string, mixed>  $data
   * @return array<string, mixed>
   */
  /**
   * Branding for outbound email (absolute logo URL required by mail clients).
   *
   * @return array<string, mixed>
   */
  public function resolveForEmail(?int $companyId = null): array
  {
    $branding = $this->resolve($companyId);
    $logoPath = $this->resolveLogoPathFromCompany($companyId);

    if ($logoPath !== null) {
      $branding['logo_url'] = $this->resolveLogoUrl($logoPath, true);
    }

    return $branding;
  }

  public function mergeIntoMailData(array $data, ?int $companyId = null): array
  {
    $data['emailBranding'] = $data['emailBranding'] ?? $this->resolveForEmail($companyId);

    return $data;
  }

  /**
   * Full path to the company logo on disk (for inline embedding in email).
   */
  public function resolveLogoFilesystemPath(?int $companyId = null): ?string
  {
    $relative = $this->resolveLogoPathFromCompany($companyId);
    if ($relative === null) {
      return null;
    }

    $relative = ltrim(preg_replace('#^storage/#', '', $relative) ?? '', '/');

    return PublicStorageDisk::readablePath($relative);
  }

  /**
   * Send branded HTML mail with logo embedded inline (CID).
   * Avoids broken images when Gmail cannot reach localhost/private URLs.
   *
   * @param  callable(\Illuminate\Mail\Message): void  $configureMessage
   */
  public function sendBrandedEmail(string $view, array $data, callable $configureMessage, ?int $companyId = null): void
  {
    Mail::send([], [], function ($message) use ($view, $data, $configureMessage, $companyId) {
      $branding = $data['emailBranding'] ?? $this->resolveForEmail($companyId);
      $logoPath = $this->resolveLogoFilesystemPath($companyId);

      if ($logoPath !== null) {
        $branding['logo_url'] = $message->embed($logoPath);
      }

      $data['emailBranding'] = $branding;
      $message->html(view($view, $data)->render());
      $configureMessage($message);
    });
  }

  private function resolveCompany(?int $companyId): ?Company
  {
    if ($companyId !== null && $companyId > 0) {
      $company = Company::query()->find($companyId);
      if ($company) {
        return $company;
      }
    }

    $request = request();
    $fromRequest = $request?->attributes->get('company');
    if ($fromRequest instanceof Company) {
      return $fromRequest;
    }

    $contextId = CompanyContext::id();
    if ($contextId !== null) {
      return Company::query()->find($contextId);
    }

    return null;
  }

  private function resolveLogoPathFromCompany(?int $companyId): ?string
  {
    $company = $this->resolveCompany($companyId);
    if (!$company) {
      return null;
    }

    $stored = Settings::query()
      ->where('company_id', $company->id)
      ->whereIn('name', self::SETTING_KEYS)
      ->pluck('value', 'name')
      ->all();

    $logoPath = $company->logo ?: ($stored['company_logo'] ?? null);

    return is_string($logoPath) && trim($logoPath) !== '' ? trim($logoPath) : null;
  }

  /**
   * @param  bool  $absolute  Mail clients require a full https URL for embedded images.
   */
  private function resolveLogoUrl(?string $logoPath, bool $absolute = false): ?string
  {
    $logoPath = is_string($logoPath) ? trim($logoPath) : '';
    if ($logoPath === '') {
      return null;
    }

    $logoPath = ltrim(preg_replace('#^storage/#', '', $logoPath) ?? $logoPath, '/');

    if (!PublicStorageDisk::exists($logoPath)) {
      return null;
    }

    $url = PublicStorageDisk::url($logoPath);

    if (!$url) {
      return null;
    }

    if (!$absolute) {
      return $url;
    }

    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      return $url;
    }

    return $this->absoluteUrlForCurrentDomain($url);
  }

  /**
   * Build a full URL using the current HTTP host (localhost, staging, production).
   */
  private function absoluteUrlForCurrentDomain(string $relativePath): string
  {
    $relativePath = '/' . ltrim($relativePath, '/');

    $request = request();
    if ($request && $request->getHttpHost()) {
      $root = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');

      return $root . $relativePath;
    }

    return rtrim((string) config('app.url'), '/') . $relativePath;
  }

  /**
   * @return array<string, mixed>
   */
  private function decodeBrandingJson(mixed $raw): array
  {
    if (is_array($raw)) {
      return $raw;
    }

    if (!is_string($raw) || trim($raw) === '') {
      return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
  }

  private function normalizeColor(?string $color, string $fallback): string
  {
    $color = is_string($color) ? trim($color) : '';
    if ($color === '') {
      return $fallback;
    }

    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
      return $color;
    }

    return $fallback;
  }

  /**
   * @return array<string, mixed>
   */
  private function neutralBranding(): array
  {
    return [
      'company_id' => null,
      'name' => (string) config('app.name'),
      'logo_url' => null,
      'primary_color' => '#2563eb',
      'secondary_color' => '#1e3a8a',
      'address' => '',
      'phone' => '',
      'email' => '',
      'city' => '',
      'country' => '',
      'footer_lines' => [],
      'app_url' => URL::to('/'),
    ];
  }
}
