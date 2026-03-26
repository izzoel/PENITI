<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verifikasi Surat Cuti</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            margin: 20px;
        }

        .card {
            max-width: 760px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
        }

        .ok {
            color: #065f46;
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .bad {
            color: #7f1d1d;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .meta {
            margin-top: 12px;
            font-size: 14px;
            color: #374151;
        }

        .meta dt {
            font-weight: 600;
        }

        .meta dd {
            margin: 0 0 8px 0;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Verifikasi Dokumen: Surat Cuti PNS</h2>

        @if (!$entry)
            <p class="bad">Dokumen tidak ditemukan atau hash tidak valid.</p>
        @else
            @if ($valid)
                <p class="ok">Dokumen <strong>ASLI</strong>. Checksum: <strong>{{ $checksum }}</strong></p>
            @else
                <p class="bad">Checksum tidak cocok. Dokumen <strong>TIDAK VALID</strong>.</p>
            @endif

            <dl class="meta">
                <dt>Nomor Surat</dt>
                <dd>{{ $entry->nomor_surat ?? '-' }}</dd>

                <dt>Status</dt>
                <dd>{{ strtoupper(str_replace('_', ' ', $entry->status)) }}</dd>

                <dt>Pemohon</dt>
                <dd>{{ $entry->pegawai->nama }} (NIP: {{ $entry->pegawai->nip ?? '-' }})</dd>

                <dt>Jenis Cuti</dt>
                <dd>{{ ucfirst(str_replace('_', ' ', $entry->jenis)) }}</dd>

                <dt>Periode</dt>
                <dd>
                    {{ format_tanggal($entry->tanggal_mulai) }} –
                    {{ format_tanggal($entry->tanggal_selesai) }} ({{ $entry->lama_hari }} hari kerja)
                    {{-- {{ optional($entry->tanggal_mulai)->format('d/m/Y') }} –
                    {{ optional($entry->tanggal_selesai)->format('d/m/Y') }} ({{ $entry->lama_hari }} hari kerja) --}}
                </dd>

                <dt>Diterbitkan</dt>
                <dd>{{ format_tanggal($entry->diterbitkan_at) ?? '-' }}</dd>
                {{-- <dd>{{ optional($entry->diterbitkan_at)->format('d/m/Y H:i') ?? '-' }}</dd> --}}
            </dl>

            <p style="font-size:12px;color:#6b7280">Halaman ini dimuat dari {{ config('app.name') }}
                ({{ config('app.url') }}).</p>
        @endif
    </div>
</body>

</html>
