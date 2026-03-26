<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Formulir Permintaan & Pemberian Cuti</title>
    <style>
        @page {
            margin: 18mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .small {
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        h1 {
            font-size: 16px;
            text-align: center;
            margin: 6px 0 8px;
            letter-spacing: .2px;
        }

        .meta-head {
            font-size: 11px;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .top-right {
            position: absolute;
            right: 0;
            top: 0;
            text-align: left;
            font-size: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .tb th,
        .tb td {
            border: 1px solid #222;
            padding: 6px 8px;
            vertical-align: top;
        }

        .tb th.sect {
            background: #f6f6f6;
            font-weight: 700;
            text-align: left;
        }

        .label {
            width: 18%;
            white-space: nowrap;
        }

        .label-wide {
            width: 22%;
            white-space: nowrap;
        }

        .box {
            display: inline-block;
            width: 13px;
            height: 13px;
            border: 1px solid #222;
            vertical-align: -2px;
            margin-right: 6px;
        }

        .checked {
            background: #000;
        }

        .sign-box {
            height: 80px;
            border: 1px dashed #aaa;
        }

        .grid-jenis {
            width: 100%;
        }

        .grid-jenis td {
            width: 50%;
            padding: 4px 8px;
            border: 0;
        }

        .no-border td,
        .no-border th {
            border: 0 !important;
        }

        .catatan-wrap {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 8px;
        }

        .tb-mini {
            border-collapse: collapse;
            width: 100%;
        }

        .tb-mini th,
        .tb-mini td {
            border: 1px solid #222;
            padding: 5px 6px;
        }

        .footnote {
            margin-top: 8px;
            font-size: 11px;
            line-height: 1.4;
        }

        /* Catatan Cuti */
        .tb-catatan {
            width: 100%;
            border-collapse: collapse;
        }

        /* .tb-catatan td {
            border: 1px solid #222;
            padding: 0;
            vertical-align: top;
        } */

        .tb-mini.nested {
            width: 100%;
            border-collapse: collapse;
        }

        .tb-mini.nested th,
        .tb-mini.nested td {
            border: 1px solid #222;
            padding: 5px 6px;
        }



        .qr-svg svg {
            width: 120px;
            height: 120px;
            display: inline-block;
        }

        .preline {
            white-space: pre-line;
        }

        /* alamat tetap rapi mengikuti line-break */
        .sect {
            background: #eee;
        }

        .fw-bold {
            font-weight: 700
        }

        .text-underline {
            text-decoration: underline;
        }

        /* opsional: header abu-abu */
    </style>
</head>

<body>

    <div style="position: relative; min-height: 110px;">
        @php
            $tanggal_surat = $entry->status === 'draft' ? $entry->created_at : $entry->updated_at ?? $entry->created_at;
        @endphp
        <div class="top-right">
            Rantau, {{ format_tanggal($tanggal_surat) ?? '-' }}<br>

            Kepada Yth. <br>
            <strong>{{ format_nama($entry->skpd->pimpinan) ?? '.' }}</strong><br>
            <strong>{{ ucfirst($entry->skpd->jabatan_pimpinan) ?? '-' }}</strong><br>
            Di.<br>
            Tempat.<br>
        </div>
    </div>

    <div>
        <h1>FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</h1>
    </div>

    {{-- ================= I. DATA PEGAWAI ================= --}}
    <table class="tb">
        <tr>
            <th class="sect" colspan="4">I. DATA PEGAWAI</th>
        </tr>
        <tr>
            <td class="label">Nama</td>
            <td>{{ $entry->pegawai->nama ?? '-' }}</td>
            <td class="label">NIP</td>
            <td>{{ $entry->pegawai->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td>{{ $entry->pegawai->jabatan ?? '-' }}</td>

            <td class="label">Masa Kerja</td>
            <td>{{ masa_kerja($entry->pegawai->nip) ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label-wide">Unit Kerja</td>
            <td colspan="3">{{ $entry->skpd->unit_kerja ?? ($entry->skpd->nama ?? '-') }}</td>
        </tr>

        {{-- ================ II. JENIS CUTI YANG DIAMBIL ================ --}}
        @php
            $jenisList = [
                'tahunan' => '1. Cuti Tahunan',
                'besar' => '2. Cuti Besar',
                'sakit' => '3. Cuti Sakit',
                'melahirkan' => '4. Cuti Melahirkan',
                'penting' => '5. Cuti Karena Alasan Penting',
                'diluar_tanggungan' => '6. Cuti di Luar Tanggungan Negara',
            ];
        @endphp
        <tr>
            <th class="sect" colspan="4">II. JENIS CUTI YANG DIAMBIL **</th>
        </tr>
        <tr>
            <td colspan="4" style="padding: 0;">
                <table class="grid-jenis">
                    <tr>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'tahunan' ? 'checked' : '' }}"></span>
                            {{ $jenisList['tahunan'] }}
                        </td>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'besar' ? 'checked' : '' }}"></span>
                            {{ $jenisList['besar'] }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'sakit' ? 'checked' : '' }}"></span>
                            {{ $jenisList['sakit'] }}
                        </td>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'melahirkan' ? 'checked' : '' }}"></span>
                            {{ $jenisList['melahirkan'] }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'penting' ? 'checked' : '' }}"></span>
                            {{ $jenisList['penting'] }}
                        </td>
                        <td>
                            <span class="box {{ (strtolower($entry->kuota->jenis) ?? null) === 'diluar_tanggungan' ? 'checked' : '' }}"></span>
                            {{ $jenisList['diluar_tanggungan'] }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- ================= III. ALASAN CUTI ================= --}}
        <tr>
            <th class="sect" colspan="4">III. ALASAN CUTI</th>
        </tr>
        <tr>
            <td colspan="4" style="height: 70px;">{{ $entry->alasan ?? '-' }}</td>
        </tr>

        {{-- ================= IV. LAMANYA CUTI ================= --}}
        <tr>
            <th class="sect" colspan="4">IV. LAMANYA CUTI</th>
        </tr>
        <tr>
            <td class="label-wide">Selama (hari/bulan/tahun)*</td>
            <td>{{ $entry->lama_hari }} hari kerja</td>
            <td class="label">Mulai Tanggal</td>
            <td>
                {{ format_tanggal($entry->tanggal_mulai) . ' s/d ' . format_tanggal($entry->tanggal_selesai) }}
            </td>
        </tr>

        {{-- ================= V. CATATAN CUTI ================= --}}
        @php
            $y = $entry->tanggal_mulai ? format_tahun($entry->tanggal_mulai) : now()->year;
            // $snap = $snapshot ?? [];
            $n2 = $y - 2 ?? 0;
            $n1 = $y - 1 ?? 0;
            $n = $y;
        @endphp
        <tr>
            <th class="sect" colspan="4">V. CATATAN CUTI ***</th>
        </tr>
        {{-- @php
            $y = optional($cuti->tanggal_mulai)->year ?? now()->year;
            $n2 = $snapshot[$y - 2]['sisa'] ?? 0;
            $n1 = $snapshot[$y - 1]['sisa'] ?? 0;
            $n = $snapshot[$y]['sisa'] ?? 0;
        @endphp --}}

        {{-- @php
            // Ambil Cuti Besar di public function pdf(PermohonanCuti $cuti)
            // mis. $saldo['besar']['sisa'], atau $saldoBesar, atau field di model.
            $sisaBesar = $sisa['besar'] ?? ($saldo['besar']['sisa'] ?? ($saldoBesar ?? ($cuti->saldo_besar ?? null)));

        @endphp

        @php
            // Ambil Cuti Sakit di public function pdf(PermohonanCuti $cuti)
            $sisaSakit = $sisa['sakit'] ?? ($saldo['sakit']['sisa'] ?? ($saldoSakit ?? ($cuti->saldo_sakit ?? null)));
        @endphp

        @php
            // Ambil Cuti Melahirkan di public function pdf(PermohonanCuti $cuti)
            $sisaMelahirkan = $sisa['melahirkan'] ?? ($saldo['melahirkan']['sisa'] ?? ($saldoMelahirkan ?? ($cuti->saldo_melahirkan ?? null)));
        @endphp

        @php
            // Ambil Cuti Penting di public function pdf(PermohonanCuti $cuti)
            $sisaPenting = $sisa['penting'] ?? ($saldo['penting']['sisa'] ?? ($saldoPenting ?? ($cuti->saldo_penting ?? null)));
        @endphp
        @php
            // Ambil Cuti diluar_tanggungan di public function pdf(PermohonanCuti $cuti)
            $sisaLuar = $sisa['diluar_tanggungan'] ?? ($saldo['diluar_tanggungan']['sisa'] ?? ($saldoLuar ?? ($cuti->saldo_diluar_tanggungan ?? null)));
        @endphp --}}


        <tr>
            <td colspan="4" style="padding:0;">
                {{-- tabel 2 kolom dengan garis tengah seperti contoh --}}
                <table class="tb-catatan">
                    <tr>
                        {{-- KIRI: 1. CUTI TAHUNAN --}}
                        <td style="width:58%;">
                            <table class="tb-mini nested">
                                <tr>
                                    <th colspan="3" class="center" style="font-weight:700;">1. CUTI TAHUNAN</th>
                                </tr>
                                <tr>
                                    <th style="width:28%;">Tahun</th>
                                    <th style="width:22%;">Sisa</th>
                                    <th>Keterangan</th>
                                </tr>
                                <tr>
                                    <td>N-2 ({{ $y - 2 }})</td>
                                    <td class="center">{{ $entry->n_2 ?? 0 }}</td>
                                    {{-- <td class="center">{{ $saldo->where('id_cuti_kuota', 1)->where('tahun', $y - 2)->first()->sisa ?? 0 }}</td> --}}
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>N-1 ({{ $y - 1 }})</td>
                                    <td class="center">{{ $entry->n_1 ?? 0 }}</td>
                                    {{-- <td class="center">{{ $saldo->where('id_cuti_kuota', 1)->where('tahun', $y - 1)->first()->sisa ?? 0 }}</td> --}}
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>N ({{ $y }})</td>
                                    <td class="center">{{ $entry->n ?? 0 }}</td>
                                    {{-- <td class="center">{{ $saldo->where('id_cuti_kuota', 1)->first()->sisa ?? 0 }}</td> --}}
                                    <td></td>
                                </tr>
                            </table>
                        </td>

                        {{-- KANAN: 2–6 sesuai contoh --}}
                        <td style="width:42%;">
                            <table class="tb-mini nested">
                                <tr>
                                    {{-- <th style="text-align:left;">2. CUTI BESAR : Sisa {{ $sisaBesar ?? '-' }} hari</th> --}}
                                    <th style="text-align:left;">2. CUTI BESAR :

                                    </th>
                                </tr>
                                <tr>
                                    {{-- {{ $saldo }} --}}
                                    <td style="height:20px;">
                                        <span style="float:right;">Sisa: {{ optional($saldo->where('id_cuti_kuota', 3)->first())->sisa ?? '-' }} hari</span>
                                        {{-- <span style="float:right;">Sisa: {{ $sisaBesar ?? '-' }} hari</span> --}}
                                    </td>

                                </tr>
                                <tr>
                                    <th style="text-align:left;"> 3. CUTI SAKIT :</th>
                                </tr>
                                <tr>
                                    <td style="height:20px;">
                                        <span style="float:right;">Sisa: {{ optional($saldo->where('id_cuti_kuota', 4)->first())->sisa ?? '-' }} hari</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:left;">4. CUTI MELAHIRKAN :</th>
                                </tr>
                                <tr>
                                    <td style="height:20px;">
                                        <span style="float:right;">Sisa: {{ optional($saldo->where('id_cuti_kuota', 2)->first())->sisa ?? '-' }} hari</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:left;">5. CUTI KARENA ALASAN PENTING :</th>
                                </tr>
                                <tr>
                                    <td style="height:20px;">
                                        <span style="float:right;">Sisa: {{ optional($saldo->where('id_cuti_kuota', 5)->first())->sisa ?? '-' }} hari</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="text-align:left;">6. CUTI DI LUAR TANGGUNGAN NEGARA :</th>
                                </tr>
                                <tr>
                                    <td style="height:20px;">
                                        <span style="float:right;">Sisa: {{ optional($saldo->where('id_cuti_kuota', 6)->first())->sisa ?? '-' }} hari</span>
                                    </td>
                                </tr>
                            </table>
                            <!-- beri jarak kecil sebelum bagian berikutnya -->
                            {{-- <div style="height:6px;"></div> --}}
                            {{-- ================= PEMISAH ================= --}}

                            {{-- VI. ALAMAT SELAMA MENJALANKAN CUTI --}}

                    <tr>
                        <th class="sect" colspan="4">VI. ALAMAT SELAMA MENJALANKAN CUTI</th>
                    </tr>
                    <tr>
                        <!-- nested table agar kolom rapi & tidak tabrakan dengan 4 kolom utama -->
                        <td colspan="4" style="padding:0;">
                            <table style="width:100%;">
                                <tr>
                                    <th style="border:1px solid #111; padding:6px 8px; width:45%;">Alamat</th>
                                    <th style="border:1px solid #111; padding:6px 8px; width:20%;">Telp</th>
                                    <th style="border:1px solid #111; padding:6px 8px; width:35%;">&nbsp;</th>
                                </tr>
                                <tr>
                                    <td style="vertical-align:top;">
                                        {{ $entry->alamat ?? '-' }}
                                    </td>
                                    <td style="vertical-align:top;">
                                        {{ $entry->telp ?? '-' }}
                                    </td>
                                    <td style="vertical-align:top;">
                                        <div>Hormat saya,</div>
                                        <div class="sign-box" style="height:70px; border:1px dashed #999; margin:6px 0 8px;"></div>
                                        <div class="text-underline" style="font-weight:700; text-decoration:underline;">
                                            {{ $entry->pegawai->nama ?? '-' }}
                                        </div>
                                        <div style="font-weight:700;">
                                            NIP. {{ $entry->pegawai->nip ?? '-' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ================= VI. PERTIMBANGAN ATASAN LANGSUNG ================= --}}
                    @php
                        $pilihan = [
                            'disetujui' => 'DISETUJUI',
                            'perubahan' => 'PERUBAHAN ****',
                            'ditangguhkan' => 'DITANGGUHKAN ****',
                            'tidak_disetujui' => 'TIDAK DISETUJUI ****',
                        ];

                        $markAtasan = match (true) {
                            $entry->status === 'disetujui' => 'disetujui',
                            $entry->status === 'ditangguhkan' => 'ditangguhkan',
                            $entry->status === 'ditolak' => 'tidak_disetujui',
                            in_array($entry->status, ['disetujui', 'ditolak'], true) && !empty($entry->disetujui_atasan_at) => 'disetujui',
                            // $entry->status === 'disetujui_atasan' => 'disetujui',
                            // $entry->status === 'ditangguhkan_atasan' => 'ditangguhkan',
                            // $entry->status === 'ditolak_atasan' => 'tidak_disetujui',
                            // in_array($entry->status, ['disetujui_kepala', 'ditangguhkan_kepala', 'ditolak_kepala'], true) && !empty($cuti->disetujui_atasan_at) => 'disetujui',
                            default => null,
                        };
                    @endphp


                    <tr>
                        <th class="sect" colspan="4">VII. PERTIMBANGAN ATASAN LANGSUNG **</th>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <table class="no-border" style="width:100%;">
                                <tr>
                                    @foreach ($pilihan as $k => $label)
                                        <td style="width:25%;">
                                            <span class="box {{ $markAtasan === $k ? 'checked' : '' }}"></span>
                                            {{ $label }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td colspan="2" class="center" style="padding-top:10px;">
                                        <div class="small"><strong>{{ $atasan['nama'] ?? '-' }}</strong></div>
                                        <div class="small">NIP {{ $atasan['nip'] ?? '-' }}</div>
                                        <div class="small">Diapprove Atasan Langsung Tanggal:
                                            {{ format_tanggal($entry->disetujui_atasan_at) ?? '-' }}
                                        </div>

                                        <div class="small">Catatan: ....................................................

                                        </div>
                                    </td>
                                    <td colspan="2" class="center">
                                        <div class="sign-box">
                                            {{-- @if ($cuti->ttd_atasan_path)
                                                <img src="{{ public_path('storage/' . $cuti->ttd_atasan_path) }}" style="height:75px;">
                                            @endif --}}
                                        </div>
                                        {{-- <div class="small"><strong>{{ $cuti->atasan->name ?? '-' }}</strong></div>
                        <div class="small">NIP {{ $cuti->atasan->nip ?? '-' }}</div> --}}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ================= VII. KEPUTUSAN PEJABAT BERWENANG ================= --}}
                    @php
                        $markKepala = match (true) {
                            $entry->status === 'disetujui' => 'disetujui',
                            $entry->status === 'ditangguhkan' => 'ditangguhkan',
                            $entry->status === 'ditolak' => 'tidak_disetujui',
                            default => null, // $cuti->status === 'disetujui_kepala' => 'disetujui',
                            // $cuti->status === 'ditangguhkan_kepala' => 'ditangguhkan',
                            // $cuti->status === 'ditolak_kepala' => 'tidak_disetujui',
                            // default => null,
                        };
                    @endphp
                    <tr>
                        <th class="sect" colspan="4">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI **</th>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <table class="no-border" style="width:100%;">
                                <tr>
                                    @foreach ($pilihan as $k => $label)
                                        <td style="width:25%;">
                                            <span class="box {{ $markKepala === $k ? 'checked' : '' }}"></span>
                                            {{ $label }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td colspan="2" class="center" style="padding-top:10px;">
                                        <div class="small"><strong>{{ $entry->skpd->pimpinan ?? '-' }}</strong></div>
                                        <div class="small">NIP {{ $entry->skpd->nip_pimpinan ?? '-' }}</div>
                                        <div class="small">Diapprove Pimpinan / Kepala Tanggal:
                                            {{ format_tanggal($entry->disetujui_pimpinan_at) ?? '-' }}
                                        </div>
                                        <div class="small">Nomor Surat: <strong>{{ $entry->nomor_surat }}</strong></div>
                                        {{-- <div class="small">Nomor Surat: <strong>{{ $entry->nomor_surat ?? '-' }}</strong></div> --}}
                                        <div class="small">Catatan: ....................................................</div>
                                        {{-- (...............................................)
                            <div class="small">NIP ................................</div> --}}
                                    </td>
                                    {{-- <td colspan="2" class="center">
                            <div class="sign-box">
                                @if ($cuti->ttd_kepala_path)
                                    <img src="{{ public_path('storage/' . $cuti->ttd_kepala_path) }}"
                    style="height:75px;">
                    @endif
                    </div> --}}
                                    {{-- <div class="small"><strong>{{ $cuti->kepalaSkpd->name ?? '-' }}</strong></div>
                    <div class="small">NIP {{ $cuti->kepalaSkpd->nip ?? '-' }}</div>
                    <div class="small">Tanggal:
                        {{ optional($cuti->disetujui_kepala_at)->format('d/m/Y') ?? '-' }}
                    </div>
                    <div class="small">Nomor Surat: <strong>{{ $cuti->nomor_surat ?? '-' }}</strong></div> --}}
                                    {{-- </td> --}}

                                    <td colspan="2" class="center" style="padding:6px 8px;">
                                        <table style="width:100%;">
                                            <tr>
                                                {{-- <td style="width:65%; text-align:center;">
                                        <div class="sign-box">
                                            @if ($cuti->ttd_kepala_path && file_exists(public_path('storage/' . $cuti->ttd_kepala_path)))
                                                <img src="{{ public_path('storage/' . $cuti->ttd_kepala_path) }}"
                                style="height:75px;">
                                @endif
                                </div>
                                <div class="small"><strong>{{ $cuti->kepalaSkpd->name ?? '-' }}</strong>
                                </div>
                                <div class="small">NIP {{ $cuti->kepalaSkpd->nip ?? '-' }}</div>
                                <div class="small">Tanggal:
                                    {{ optional($cuti->disetujui_kepala_at)->format('d/m/Y') ?? '-' }}
                                </div>
                                <div class="small">Nomor Surat:
                                    <strong>{{ $cuti->nomor_surat ?? '-' }}</strong>
                                </div>
                    </td> --}}

                                                <td style="width:35%; text-align:center; vertical-align:top;">
                                                    <div class="small" style="margin-top:4px;">Scan untuk verifikasi</div>
                                                    <img src="{{ $qr }}" width="120" height="120" alt="QR Verify">
                                                    <div class="small">Checksum:
                                                        <strong>{{ $entry->verif_checksum }}</strong>
                                                    </div>
                                                </td>

                                            </tr>
                                        </table>
                                    </td>


                                </tr>
                            </table>
                        </td>
                    </tr>

            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
    {{-- ================= FOOTNOTE ================= --}}
    <div class="footnote">
        <strong>Catatan:</strong><br>
        * Coret yang tidak perlu<br>
        ** Pilih salah satu dengan memberi tanda centang (✔)<br>
        *** Diisi oleh pejabat yang menangani bidang kepegawaian sebelum PNS mengajukan cuti<br>
        **** Diberi tanda centang dan alasannya<br>
        N = Cuti tahun berjalan, N-1 = Sisa cuti 1 tahun sebelumnya, N-2 = Sisa cuti 2 tahun sebelumnya
    </div>


</body>

</html>
