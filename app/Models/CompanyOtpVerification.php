<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyOtpVerification extends BaseModel
{
    protected $connection = 'mysql_central';

    protected $table = 'company_otp_verifications';

    protected $fillable = ['email', 'otp', 'expires_at', 'verified'];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    public function isValid(): bool
    {
        return !$this->verified && $this->expires_at->isFuture();
    }

    public static function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    public static function createForEmail(string $email, int $minutes = 15): self
    {
        self::query()->where('email', $email)->delete();
        $otp = self::generateOtp();
        return self::query()->create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }
}
