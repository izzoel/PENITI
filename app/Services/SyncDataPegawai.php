<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Pegawai;

class SyncDataPegawai
{
    protected int $chunkSize = 1000;

    public function handle(): void
    {
        $baseUrl = config('services.api.base_url');
        $token   = config('services.api.token');

        $pegawaiUrl = $baseUrl . '/struktur';

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'token-profil' => $token,
            ])
            ->get($pegawaiUrl, ['limit' => 1000]);

        if (!$response->successful()) {
            throw new \Exception('Gagal mengambil data Pegawai.');
        }

        $pegawaiData = $response->json('data') ?? [];

        if (empty($pegawaiData)) {
            return;
        }

        foreach (array_chunk($pegawaiData, $this->chunkSize) as $chunk) {

            $now = now();
            $batch = [];

            foreach ($chunk as $data) {
                $batch[] = [
                    'id'          => $data['id'],
                    'nip'         => $data['nip'],
                    'nama'        => $data['nama'],
                    'id_skpd'     => $data['id_skpd'],
                    'id_struktur' => $data['id_struktur'] ?? null,
                    'id_atasan'   => $data['parent_id'] ?? null,
                    'pangkat'     => $data['pangkat'] ?? null,
                    'jabatan'     => $data['jabatan'] ?? null,
                    'foto'        => $data['foto'] ?? null,
                    'plt'         => (bool) ($data['plt'] ?? false),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            Pegawai::upsert(
                $batch,
                ['id'],
                [
                    'nip',
                    'nama',
                    'id_skpd',
                    'id_struktur',
                    'pangkat',
                    'jabatan',
                    'foto',
                    'plt',
                    'updated_at',
                ]
            );
        }
    }
}
