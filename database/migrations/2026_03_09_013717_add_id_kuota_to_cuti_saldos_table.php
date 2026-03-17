<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up(): void
    // {
    //     Schema::table('cuti_saldos', function (Blueprint $table) {
    //         $table->foreignId('id_cuti_kuota')
    //             ->after('id_pegawai')
    //             ->constrained('cuti_kuotas')
    //             ->cascadeOnDelete();
    //     });
    // }

    // public function down(): void
    // {
    //     Schema::table('cuti_saldos', function (Blueprint $table) {
    //         $table->dropForeign(['id_cuti_kuota']);
    //         $table->dropColumn('id_cuti_kuota');
    //     });
    // }
};
