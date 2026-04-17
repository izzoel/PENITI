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
if (!function_exists('format_tahun')) {
    function format_tahun($tanggal)
    {
        return Carbon::parse($tanggal)->year;
    }
}

if (!function_exists('nomor_surat')) {
    function nomor_surat($data, $prefix = null)
    {
        if (!$data || !isset($data->id)) {
            return null;
        }

        $jenis = (isset($data->id_cuti_kuota) && (int) $data->id_cuti_kuota === 4)
            ? 'SAKIT'
            : 'CUTI';

        $prefix = $prefix ?: '800/' . $jenis;
        $year = now()->year;

        return sprintf('%s/%d/%06d', $prefix, $year, $data->id);
    }
}

if (!function_exists('masa_kerja')) {
    function masa_kerja($nip)
    {
        if (!$nip || strlen($nip) < 16) {
            return null;
        }

        try {
            $tmt = substr($nip, 8, 8);

            $tmtDate = Carbon::createFromFormat('Ymd', $tmt);
            $now = Carbon::now();

            $diff = $tmtDate->diff($now);

            return $diff->y . ' tahun ' . $diff->m . ' bulan ' . $diff->d . ' hari';
        } catch (\Exception $e) {
            return null;
        }
    }
}
