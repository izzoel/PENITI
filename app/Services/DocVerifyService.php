<?php

namespace App\Services;

use App\Models\Entries;

class DocVerifyService
{
    /** Buat payload deterministik untuk ditandatangani */
    public static function payload(Entries $entry): string
    {
        // Susun field kunci yang “mengesahkan” dokumen
        return implode('|', [
            'CUTI',            // prefix tipe dokumen
            $entry->id,
            $entry->id_pegawai,
            $entry->jenis,
            $entry->tanggal_mulai,
            $entry->tanggal_selesai,
            // optional($entry->tanggal_mulai)->toDateString(),
            // optional($entry->tanggal_selesai)->toDateString(),
            (int)$entry->lama_hari,
            (string)$entry->nomor_surat,
            'FINAL',           // jika mau, tambahkan penanda final
        ]);
    }

    /** Hash HMAC → string hex (pakai secret env) */
    public static function makeHash(Entries $entry): string
    {
        $secret = env('APP_KEY') ?: env('DOC_SIGNING_SECRET', 'fallback-secret');
        $raw    = hash_hmac('sha256', self::payload($entry), $secret, true); // raw bytes
        // Base32 tanpa padding biar URL-friendly & lebih pendek
        return rtrim(self::base32($raw), '=');
    }

    /** Checksum pendek untuk manusia (12–16 char) */
    public static function checksum(Entries $entry, int $len = 12): string
    {
        $secret = env('APP_KEY') ?: env('DOC_SIGNING_SECRET', 'fallback-secret');
        $hex    = hash_hmac('sha256', self::payload($entry), $secret);
        return substr(strtoupper($hex), 0, $len);
    }

    /** Terbitkan nomor surat. Sementara: format sederhana. Ganti jika perlu. */
    public static function nomorSurat(Entries $entry): string
    {
        // Contoh: 800/CUTI/2025/000123  (gunakan ID padded)
        $year = optional($entry->tanggal_mulai)->year ?? now()->year;
        return sprintf('800/CUTI/%d/%06d', $year, $entry->id);
    }

    /** Terbitkan hash/checksum & nomor_surat ke model */
    public static function issue(Entries $entry): Entries
    {
        if (!$entry->nomor_surat) {
            $entry->nomor_surat = self::nomorSurat($entry);
        }
        $entry->verif_hash     = self::makeHash($entry);
        $entry->verif_checksum = self::checksum($entry);
        $entry->diterbitkan_at = now();
        $entry->save();

        return $entry;
    }

    /** Verify: cocokkan hash terhadap payload & secret */
    public static function verify(Entries $entry): bool
    {
        return hash_equals(self::makeHash($entry), (string) $entry->verif_hash);
    }

    /** Helper Base32 */
    private static function base32(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary   = '';
        foreach (str_split($data) as $entry) $binary .= str_pad(decbin(ord($entry)), 8, '0', STR_PAD_LEFT);
        $chunks = str_split($binary, 5);
        $out = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $out .= $alphabet[bindec($chunk)];
        }
        while (strlen($out) % 8 !== 0) $out .= '=';
        return $out;
    }
}
