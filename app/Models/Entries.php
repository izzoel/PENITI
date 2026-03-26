<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entries extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_pegawai', 'id_pegawai');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function kuota()
    {
        return $this->belongsTo(CutiKuota::class, 'id_cuti_kuota');
    }

    public function skpd()
    {
        return $this->belongsTo(SKPD::class, 'id_skpd', 'id_skpd');
    }
}
