<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'otp_code',
        'expires_at',
        'verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    /**
     * Get the user that owns the OTP code.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a random OTP code.
     *
     * @return string
     */
    public static function generateCode(): string
    {
        return str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP code for the given email.
     *
     * @param string $email
     * @param int|null $userId
     * @return self
     */
    public static function createForEmail(string $email, ?int $userId = null): self
    {
        // Invalidate any existing OTP codes for this email
        self::where('email', $email)
            ->update(['verified' => true]);

        // Create a new OTP code
        return self::create([
            'user_id' => $userId,
            'email' => $email,
            'otp_code' => self::generateCode(),
            'expires_at' => now()->addMinutes(10), // OTP expires in 10 minutes
            'verified' => false,
        ]);
    }

    /**
     * Check if the OTP code is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->verified && $this->expires_at > now();
    }

    /**
     * Verify the OTP code.
     *
     * @param string $code
     * @return bool
     */
    public function verify(string $code): bool
    {
        if ($this->otp_code === $code && $this->isValid()) {
            $this->verified = true;
            $this->save();
            return true;
        }

        return false;
    }
}
