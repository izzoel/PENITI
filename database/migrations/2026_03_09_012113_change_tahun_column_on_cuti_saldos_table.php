<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->year('tahun')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cuti_saldos', function (Blueprint $table) {
            $table->tinyInteger('tahun')->change();
        });
    }
};
