<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasSubscription, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'roll_number',
        'password',
        'phone',
        'passport',
        'country',
        'institute',
        'reference',
        'target_band',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'target_band' => 'decimal:1',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Get all results for this user.
     */
    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function getAverageBandScore(): float
    {
        return $this->testAttempts()
            ->whereNotNull('overall_band')
            ->avg('overall_band') ?? 0;
    }

    public function getLatestAttempt()
    {
        return $this->testAttempts()
            ->latest()
            ->first();
    }

    public function getCompletedAttemptsCount(): int
    {
        return $this->testAttempts()
            ->where('status', 'completed')
            ->count();
    }
}
