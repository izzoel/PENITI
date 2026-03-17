<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Pegawai;

class SyncDataPegawai
{
    protected int $chunkSize = 1000;

    public function handle(): void
    {
        $govemUrl = config('services.api.govemUrl');
        $prismaUrl = config('services.api.prismaUrl');
        $prismaToken   = config('services.api.prismaToken');

        $pegawaiUrl = $prismaUrl . '/struktur';
        $officeUrl = $prismaUrl . '/offices';

        $responsePegawai = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'token-profil' => $prismaToken,
            ])
            ->get($pegawaiUrl, ['limit' => 1000]);

        $responseOffice = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'token-profil' => $prismaToken,
            ])
            ->get($officeUrl);

        if (!$responsePegawai->successful()) {
            throw new \Exception('Gagal mengambil data Pegawai.');
        }

        if (!$responseOffice->successful()) {
            throw new \Exception('Gagal mengambil data Offices.');
        }

        $pegawaiData = $responsePegawai->json('data') ?? [];
        $officeData  = $responseOffice->json('data') ?? [];
        // dd($officeData);
        // if (empty($pegawaiData)) {
        //     return;
        // }

        // $count = 0;

        foreach ($officeData as $office) {

            if (empty($office['nip'])) {
                continue;
            }

            $pegawaiData[] = [
                'id'          => $office['id'],
                'nip'         => $office['nip'],
                'nama'        => $office['pimpinan'],
                'id_skpd'     => $office['id_skpd'],
                'id_struktur' => null,
                'parent_id'   => null,
                'pangkat'     => null,
                'jabatan'     => $office['jabatan'],
                'foto'        => null,
                'plt'         => false,
            ];

            // $count++;
        }

        // dd($count, $officeData[0]);


        foreach (array_chunk($pegawaiData, $this->chunkSize) as $chunk) {

            $now = now();
            $batch = [];

            foreach ($chunk as $data) {

                $batch[] = [
                    'id'          => $data['id'],
                    'nip'         => $data['nip'],
                    'nama'        => format_nama($data['nama']),
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
                    'id_atasan',
                    'pangkat',
                    'jabatan',
                    'foto',
                    'plt',
                    'updated_at',
                ]
            );

            foreach ($chunk as $data) {

                $nip = $data['nip'];

                if (!$nip) {
                    continue;
                }

                $detailResponse = Http::timeout(15)
                    ->acceptJson()
                    ->get($govemUrl . "/pegawai/$nip/find");

                if (!$detailResponse->successful()) {
                    throw new \Exception('Gagal mengambil data Pegawai.');
                }
                $jenisKelamin = $detailResponse->json('data.jenis_kelamin');

                if ($jenisKelamin) {
                    Pegawai::where('nip', $nip)
                        ->update([
                            'jenis_kelamin' => $jenisKelamin
                        ]);
                }
            }
        }

        // $officeResponse = Http::timeout(30)
        //     ->acceptJson()
        //     ->withHeaders([
        //         'token-profil' => $prismaToken,
        //     ])
        //     ->get($officeUrl);

        // if (!$officeResponse->successful()) {
        //     throw new \Exception('Gagal mengambil data Offices.');
        // }

        // $offices = $officeResponse->json('data') ?? [];

        // foreach ($offices as $office) {

        //     $nipPimpinan = $office['nip_pimpinan'] ?? null;
        //     $idSkpd      = $office['id'] ?? null;

        //     if (!$nipPimpinan) {
        //         continue;
        //     }

        //     Pegawai::where('nip', $nipPimpinan)
        //         ->update([
        //             'kepala_skpd' => $idSkpd
        //         ]);
        // }
    }
}
