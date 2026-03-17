<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_pegawai')
                ->constrained('pegawais')
                ->cascadeOnDelete();

            $table->foreignId('id_cuti_kuota')
                ->constrained('cuti_kuotas')
                ->restrictOnDelete();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('lama_hari');

            $table->text('alasan')->nullable();

            $table->text('alamat')->nullable();
            $table->string('telp')->nullable();

            $table->string('kepada_nama')->nullable();
            $table->string('kepada_alamat')->nullable();

            $table->unsignedSmallInteger('n')->nullable();
            $table->unsignedSmallInteger('n_1')->nullable();
            $table->unsignedSmallInteger('n_2')->nullable();

            $table->text('catatan_hr')->nullable();

            $table->enum('status', [
                'draft',
                'diajukan',
                'diproses',
                'disetujui',
                'ditolak',
                'dibatalkan'
            ])->default('draft');

            $table->enum('status_atasan', [
                'menunggu',
                'disetujui',
                'ditangguhkan',
                'ditolak'
            ])->nullable();

            $table->enum('status_pimpinan', [
                'menunggu',
                'disetujui',
                'ditangguhkan',
                'ditolak'
            ])->nullable();

            $table->string('nomor_surat')->nullable();

            $table->string('verif_hash', 80)->nullable();
            $table->string('verif_checksum', 16)->nullable();

            $table->foreignId('id_atasan')
                ->nullable()
                ->constrained('pegawais')
                ->nullOnDelete();

            $table->timestamp('disetujui_atasan_at')->nullable();

            $table->foreignId('id_skpd')
                ->nullable()
                ->constrained('skpds')
                ->nullOnDelete();

            $table->timestamp('disetujui_pimpinan_at')->nullable();

            $table->timestamp('diterbitkan_at')->nullable();

            $table->string('ttd_atasan_path')->nullable();
            $table->string('ttd_pimpinan_path')->nullable();
            $table->string('lampiran_path')->nullable();

            $table->json('rincian_potong')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
