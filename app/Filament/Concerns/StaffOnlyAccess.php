<?php

namespace App\Filament\Concerns;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;

/**
 * Locks a resource to staff users (accounting / commercial).
 *
 * Filament checks these static methods for navigation visibility and
 * for every resource page (list / create / view / edit, including
 * direct URLs), so overriding them closes both the menu and the URLs
 * for plain requesters.
 */
trait StaffOnlyAccess
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && Roles::isStaff($user);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccess();
    }
}
