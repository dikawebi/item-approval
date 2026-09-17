<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for role names the workflow depends on.
 *
 * - STAFF: users holding any of these roles see every request and may
 *   open the Administration resources (Users, Roles, Number Sequences,
 *   Item Groups). Plain users without these roles are requesters.
 * - PROTECTED: renaming or deleting these roles would silently break
 *   the Classify / Create-in-D365 gating, so the UI locks them.
 */
final class Roles
{
    public const STAFF = ['accounting', 'commercial'];

    public const PROTECTED = ['accounting', 'commercial'];

    /**
     * Keterangan kemampuan tiap role, ditampilkan di halaman Roles
     * agar admin tahu persis apa yang dibuka oleh tiap role.
     */
    public const DESCRIPTIONS = [
        'accounting' => 'Akuntansi — Classify & Assign, Request Info, dan Reject atas request '
            .'berstatus pending/needs_info. Melihat seluruh antrean request dan membuka menu Administration.',
        'commercial' => 'Komersial — Create in D365 (satuan & massal) atas request berstatus '
            .'classified/create_failed, serta View Error. Melihat seluruh antrean request dan membuka menu Administration.',
    ];

    public static function description(string $roleName): string
    {
        return self::DESCRIPTIONS[$roleName]
            ?? 'Role kustom — tanpa aksi workflow khusus dan bukan staff, sehingga diperlakukan '
            .'sebagai requester: hanya melihat request sendiri dan tanpa menu Administration. '
            .'Tambahkan role accounting/commercial bila perlu akses lebih.';
    }

    public static function isStaff(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(self::STAFF);
    }
}
