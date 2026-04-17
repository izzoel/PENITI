<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Gate;     // ← WAJIB: import Gate
use Illuminate\Support\Facades\Storage; 

// class CutiHistoryItemResource extends JsonResource
// {
//     /**
//      * @property \App\Models\PermohonanCuti $resource
//      */
//     public function toArray($request)
//     {
//         $pemohon = $this->whenLoaded('pemohon');
//         $skpd    = $pemohon?->skpd;
//         $atasan  = $this->whenLoaded('atasan');
//         $kepala  = $this->whenLoaded('kepalaSkpd');

//         return [
//             'id'               => (int) $this->id,
//             'jenis'            => (string) $this->jenis,
//             'status'           => (string) $this->status,
//             'tanggal_mulai'    => optional($this->tanggal_mulai)->toDateString(),
//             'tanggal_selesai'  => optional($this->tanggal_selesai)->toDateString(),
//             'lama_hari'        => (int) ($this->lama_hari ?? 0),
//             'alasan'           => $this->alasan,
//             'created_at'       => optional($this->created_at)->toIso8601String(),
//             'updated_at'       => optional($this->updated_at)->toIso8601String(),

//             'pemohon' => [
//                 'id'        => $pemohon?->id,
//                 'name'      => $pemohon?->name,
//                 'nip'       => $pemohon?->nip,
//                 'skpd_id'   => $pemohon?->skpd_id,
//                 'skpd_name' => $skpd?->nama,
//             ],

//             'atasan' => [
//                 'id'   => $atasan?->id,
//                 'name' => $atasan?->name,
//             ],

//             'kepala' => [
//                 'id'   => $kepala?->id,
//                 'name' => $kepala?->name,
//             ],

//             // opsional: flag untuk UI
//             'is_milik_saya' => auth()->id() === $this->user_id,
//             'is_draft'      => $this->status === 'draft',
//         ];
//     }
// }
class CutiHistoryItemResource extends JsonResource
{
    public function toArray($request)
    {
        $pemohon = $this->whenLoaded('pemohon');
        $skpd    = $pemohon?->skpd;
        $atasan  = $this->whenLoaded('atasan');
        $kepala  = $this->whenLoaded('kepalaSkpd');

        return [
            'id'             => $this->id,
            'jenis'          => $this->jenis,
            'status'         => $this->status,
            'tanggal_mulai'  => optional($this->tanggal_mulai)->toDateString(),
            'tanggal_selesai'=> optional($this->tanggal_selesai)->toDateString(),
            'lama_hari'      => $this->lama_hari,
            'alasan'         => $this->alasan,

            'created_at'     => optional($this->created_at)->toIso8601String(),
            'updated_at'     => optional($this->updated_at)->toIso8601String(),

            // lampiran url (kalau ada)
            'lampiran_url'   => $this->lampiran_path
                ? Storage::disk('public')->url($this->lampiran_path)
                : null,

            // relasi ringkas sesuai model Dart kamu
            'pemohon' => [
                'id'         => $pemohon?->id,
                'name'       => $pemohon?->name,
                'nip'        => $pemohon?->nip,
                'skpd_id'    => $pemohon?->skpd_id,
                'skpd_name'  => $skpd?->nama,
            ],
            'atasan'  => [
                'id'   => $atasan?->id,
                'name' => $atasan?->name,
            ],
            'kepala'  => [
                'id'   => $kepala?->id,
                'name' => $kepala?->name,
            ],

            // flags untuk UI
            'is_milik_saya'  => (bool) ($this->user_id === Auth::id()),
            'is_draft'       => (bool) ($this->status === 'draft'),
        ];
    }
}