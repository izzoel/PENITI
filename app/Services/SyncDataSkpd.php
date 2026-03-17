<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\SKPD as Skpd;

class SyncDataSkpd
{
    protected int $chunkSize = 1000;

    public function handle(): void
    {
        $prismaUrl = config('services.api.prismaUrl');
        $prismaToken   = config('services.api.prismaToken');

        $skpdUrl = $prismaUrl . '/offices';

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'token-profil' => $prismaToken,
            ])
            ->get($skpdUrl, ['limit' => 1000]);

        if (!$response->successful()) {
            throw new \Exception('Gagal mengambil data Pegawai.');
        }
        $skpdData = $response->json('data') ?? [];


        if (empty($skpdData)) {
            return;
        }

        foreach (array_chunk($skpdData, $this->chunkSize) as $chunk) {

            $now = now();
            $batch = [];

            foreach ($chunk as $data) {

                $batch[] = [
                    'id'          => $data['id'],
                    'id_skpd'     => $data['id_skpd'],
                    'uuid'        => $data['uuid'],
                    'nama'        => $data['name'],
                    'pimpinan' => $data['pimpinan'] ?? null,
                    'nip_pimpinan'   => $data['nip'] ?? null,
                    'jabatan_pimpinan'     => $data['jabatan'] ?? null,
                    'alamat'     => $data['alamat'] ?? null,
                    'telp'        => $data['telepon'] ?? null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            Skpd::upsert(
                $batch,
                ['id'],
                [
                    'id_skpd',
                    'uuid',
                    'nama',
                    'pimpinan',
                    'nip_pimpinan',
                    'jabatan_pimpinan',
                    'alamat',
                    'telp',
                    'updated_at',
                ]
            );
        }
    }
}
