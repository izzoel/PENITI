<?php

namespace App\Http\Controllers;

use App\Models\CutiSaldo;
use App\Models\Entries;
use App\Models\Pegawai;
use App\Services\DocVerifyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class PdfController extends Controller
{
    public function pdf($id)
    {
        $user = auth()->user();
        $entry = Entries::find($id);
        $pegawai = Pegawai::find($entry->id_pegawai);
        $saldo = CutiSaldo::where('id_user', $entry->id_pegawai)->get();
        $atasan = Pegawai::where('id_skpd', $entry->id_skpd)
            ->where('id_struktur', $pegawai->id_atasan)
            ->first();

        $verifyUrl = route('verify', $entry->verif_hash);

        $builder = new Builder(
            writer: new PngWriter(),
            data: $verifyUrl,
            size: 140,
            margin: 1

        );

        $result = $builder->build();

        $qr = $result->getDataUri();

        // return view('pages.laporan.pdf', compact('entry', 'saldo', 'atasan', 'qr'));
        $pdf = Pdf::loadView(
            'pages.laporan.pdf',
            [
                'entry' => $entry,
                'saldo' => $saldo,
                'pegawai' => $pegawai,
                'atasan' => $atasan,
                'qr'      => $qr
            ]
        )->setPaper('a4');

        $filename = str_replace(['/', '\\'], '-', 'Surat_Cuti_' . ($entry->create_at ?: 'ID-' . $entry->id)) . '.pdf';
        return $pdf->download($filename);
    }

    public function verify(string $hash)
    {
        $entry = Entries::whereNotNull('verif_hash')
            ->where('verif_hash', $hash)
            ->first();

        $valid = false;
        $checksum = null;

        if ($entry) {
            $valid    = DocVerifyService::verify($entry);
            $checksum = $entry->verif_checksum;
        }

        // Halaman publik: tampilkan status, nomor, checksum
        return view('pages.laporan.verifikasi', compact('entry', 'valid', 'checksum'));
    }
}
