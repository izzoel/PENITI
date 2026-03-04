<?php

use Spatie\Permission\Models\Role;

if (!function_exists('format_nama')) {
    function format_nama($nama)
    {
        return collect(explode(',', $nama ?? ''))
            ->map(function ($item, $i) {
                return $i === 0
                    ? ucwords(strtolower(trim($item)))
                    : trim($item);
            })
            ->implode(', ');
    }
}


if (!function_exists('akses')) {
    /**
     * Cek apakah user login punya permission yang diawali prefix tertentu
     * @param string $prefix Contoh: 'u'
     * @return bool
     */
    function akses(string $prefix): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        $role = $user->roles->first();
        if (!$role) return false;

        $roleModel = Role::find($role->id);
        if (!$roleModel) return false;

        return $roleModel->permissions->contains(function ($perm) use ($prefix) {
            return str_starts_with($perm->name, $prefix . '_');
        });
    }
}
