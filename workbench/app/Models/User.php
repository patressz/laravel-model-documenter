<?php

declare(strict_types=1);

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ?string $phone
 * @property string $slug
 * @property ?string $bio
 * @property ?string $description
 * @property ?string $summary
 * @property ?int $age
 * @property int $points
 * @property ?float $salary
 * @property float $rating
 * @property float $balance
 * @property int $views
 * @property int $downloads
 * @property ?\Illuminate\Support\Carbon $birth_date
 * @property ?string $preferred_time
 * @property ?string $last_login
 * @property ?string $verified_at
 * @property ?int $graduation_year
 * @property bool $is_active
 * @property bool $is_verified
 * @property bool $receives_notifications
 * @property ?string $preferences
 * @property ?string $metadata
 * @property ?string $external_id
 * @property ?string $last_ip
 * @property ?string $device_mac
 * @property ?int $company_id
 * @property ?int $department_id
 * @property ?int $manager_id
 * @property string $status
 * @property string $role
 * @property ?string $avatar
 * @property ?string $location
 * @property ?string $remember_token
 * @property ?\Illuminate\Support\CarbonImmutable $deleted_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Workbench\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'birth_date' => 'date',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
