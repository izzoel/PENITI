<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class PermohonanCuti extends Model
{
    protected $table = 'permohonan_cuti';

    protected $fillable = [
        'user_id',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'lama_hari',
        'alasan',
        'alamat_saat_cuti',
        'telp_saat_cuti',
        'kepada_nama',
        'kepada_alamat',
        'n',
        'n_1',
        'n_2',
        'catatan_hr',
        'status',
        'atasan_id',
        'disetujui_atasan_at',
        'kepala_skpd_id',
        'disetujui_kepala_at',
        'lampiran_path',
        'rincian_potong',
        'nomor_surat',
        'verif_hash',
        'verif_checksum',
        'diterbitkan_at',
        'ttd_atasan_path',
        'ttd_kepala_path',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'disetujui_atasan_at' => 'datetime',
        'disetujui_kepala_at' => 'datetime',
    ];

    // Relasi
    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function kepalaSkpd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_skpd_id');
    }
}
