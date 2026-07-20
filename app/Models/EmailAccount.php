<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends BaseModel
{
  public const STATUS_ACTIVE = 'active';

  public const STATUS_INACTIVE = 'inactive';

  protected $fillable = [
    'company_id',
    'email',
    'app_password',
    'display_name',
    'status',
  ];

  protected $hidden = [
    'app_password',
  ];

  public static array $rules = [
    'email' => 'required|email|max:255',
    'app_password' => 'required|string|min:16',
    'display_name' => 'nullable|string|max:255',
    'status' => 'required|in:active,inactive',
    'user_ids' => 'nullable|array',
    'user_ids.*' => 'integer|distinct',
  ];

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'email_account_user')
      ->withTimestamps();
  }

  public function scopeActive($query)
  {
    return $query->where('status', self::STATUS_ACTIVE);
  }

  public function isActive(): bool
  {
    return $this->status === self::STATUS_ACTIVE;
  }

  public function setAppPasswordAttribute(?string $value): void
  {
    if ($value === null || $value === '') {
      return;
    }

    $this->attributes['app_password'] = Crypt::encryptString($value);
  }

  public function getDecryptedAppPassword(): ?string
  {
    $encrypted = $this->attributes['app_password'] ?? null;
    if (!is_string($encrypted) || trim($encrypted) === '') {
      return null;
    }

    try {
      return Crypt::decryptString($encrypted);
    } catch (\Throwable $e) {
      return null;
    }
  }

  public function displayLabel(): string
  {
    $email = trim((string) $this->email);
    $name = trim((string) ($this->display_name ?? ''));

    return $name !== '' ? "{$name} <{$email}>" : $email;
  }
}
