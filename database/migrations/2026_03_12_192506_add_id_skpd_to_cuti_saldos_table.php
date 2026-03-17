<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_skpd')->after('id_cuti_kuota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->dropColumn(['id_skpd']);
        });
    }
};
