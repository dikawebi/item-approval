<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * Filament v5 denies panel access with 403 for user models that do
     * not implement this contract whenever APP_ENV is not 'local'
     * (including 'testing' and 'production').
     *
     * Every authenticated user may enter the panel — requesters need it
     * for their own requests. What each role may *do* inside is enforced
     * per resource (StaffOnlyAccess, ItemCreationRequestResource::can*).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

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
        ];
    }
}
