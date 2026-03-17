<?php

use Carbon\Carbon;

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

    function akses(string $prefix, ?string $module = null): bool
    {
        $user = auth()->user();
        if (!$user || !$module) return false;

        return $user->hasPermissionTo($prefix . '_' . $module);
    }
}

if (!function_exists('format_tanggal')) {
    function format_tanggal($tanggal)
    {
        return Carbon::parse($tanggal)
            ->locale('id')
            ->translatedFormat('d F Y');
    }
}
