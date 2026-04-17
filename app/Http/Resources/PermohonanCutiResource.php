<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PermohonanCutiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'nomor_surat' => $this->nomor_surat,
            'status' => $this->status,

            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'lama_hari' => $this->lama_hari,

            'alasan' => $this->alasan,
            'alamat' => $this->alamat,
            'telp' => $this->telp,
            'lampiran_path' => $this->lampiran_path,
            'lampiran_url' => $this->lampiran_path ? Storage::disk('public')->url($this->lampiran_path) : null,

            // Sisa cuti per tahun
            'sisa_cuti' => [
                'tahun_ini' => $this->n,
                'tahun_minus_1' => $this->n_1,
                'tahun_minus_2' => $this->n_2,
            ],

            // Relasi Pegawai
            'pegawai' => [
                'id' => $this->pegawai->id ?? null,
                'nama' => $this->pegawai->nama ?? null,
                'nip' => $this->pegawai->nip ?? null,
            ],

            // Relasi Jenis Cuti
            'kuota' => [
                'id' => $this->kuota->id ?? null,
                'nama' => $this->kuota->jenis ?? null,
            ],

            // Relasi SKPD
            'skpd' => [
                'id' => $this->skpd->id ?? null,
                'nama' => $this->skpd->nama ?? null,
            ],

            'kepada_nama' => $this->kepada_nama,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
