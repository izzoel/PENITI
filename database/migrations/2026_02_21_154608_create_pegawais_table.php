<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();

            $table->string('nip')->unique();
            $table->string('nama');

            $table->unsignedBigInteger('id_skpd')->index();
            $table->unsignedBigInteger('id_struktur')->nullable()->index();
            $table->unsignedBigInteger('id_atasan')->nullable()->index();

            $table->string('pangkat')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('plt')->default(false);

            $table->timestamps();

            // Optional foreign key (jika ada tabelnya)
            // $table->foreign('id_skpd')->references('id')->on('skpds')->cascadeOnDelete();
            // $table->foreign('id_struktur')->references('id')->on('strukturs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
