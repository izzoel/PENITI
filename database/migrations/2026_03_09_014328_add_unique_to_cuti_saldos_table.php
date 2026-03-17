<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->unique(['id_pegawai', 'id_cuti_kuota', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->dropUnique(['id_pegawai', 'id_cuti_kuota', 'tahun']);
        });
    }
};
