<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permohonan_cuti', function (Blueprint $t) {
            $t->id();

            // Pemohon
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Jenis & periode cuti
            $t->enum('jenis', [
                'tahunan',
                'besar',
                'sakit',
                'melahirkan',
                'penting',
                'diluar_tanggungan'
            ]);

            $t->date('tanggal_mulai');
            $t->date('tanggal_selesai');
            $t->unsignedSmallInteger('lama_hari'); // simpan hasil hitung (hari kerja/kalender sesuai kebijakan)
            $t->text('alasan')->nullable();

            // Alamat/telepon saat cuti (bagian VI form)
            $t->text('alamat_saat_cuti')->nullable();
            $t->string('telp_saat_cuti')->nullable();

            // (Opsional) info tujuan surat
            $t->string('kepada_nama')->nullable();
            $t->string('kepada_alamat')->nullable();

            // Catatan N / N-1 / N-2 (bagian V form) - opsional
            $t->unsignedSmallInteger('n')->nullable();
            $t->unsignedSmallInteger('n_1')->nullable();
            $t->unsignedSmallInteger('n_2')->nullable();
            $t->text('catatan_hr')->nullable(); // catatan kepegawaian

            // Alur persetujuan
            $t->enum('status', [
                'draft',
                'diajukan',
                'disetujui_atasan',
                'ditangguhkan_atasan',
                'ditolak_atasan',
                'disetujui_kepala',
                'ditangguhkan_kepala',
                'ditolak_kepala',
                'dibatalkan'
            ])->default('draft');

            // Penandatangan/pejabat terkait
            $t->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('disetujui_atasan_at')->nullable();

            $t->foreignId('kepala_skpd_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('disetujui_kepala_at')->nullable();

            // Lampiran (surat dokter dsb)
            $t->string('lampiran_path')->nullable();

            $t->timestamps();

            // Index yang membantu query
            $t->index(['user_id', 'tanggal_mulai']);
            $t->index(['atasan_id']);
            $t->index(['kepala_skpd_id']);
            $t->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan_cuti');
    }
};
