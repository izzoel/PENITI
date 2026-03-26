<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiSaldo extends Model
{
    protected $fillable = ['id_user', 'id_cuti_kuota', 'id_skpd', 'tahun', 'jenis', 'kuota', 'terpakai', 'sisa', 'expired'];

    protected $casts =
    [
        'tahun' => 'integer',
        'kuota' => 'integer',
        'terpakai' => 'integer',
        'sisa' => 'integer',
        'expired' => 'date',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id_pegawai');
    }

    public function kuotas()
    {
        return $this->belongsTo(CutiKuota::class, 'id_cuti_kuota');
    }

    public function skpd()
    {
        return $this->belongsTo(SKPD::class, 'id_skpd', 'id_skpd');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
