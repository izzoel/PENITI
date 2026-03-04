<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $fillable = [
        'nip',
        'nama',
        'id_skpd',
        'id_struktur',
        'id_atasan',
        'pangkat',
        'jabatan',
        'foto',
        'plt'
    ];

    protected $casts = [
        'plt' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id_pegawai');
    }
    /*
    |--------------------------------------------------------------------------
    | Relationships (Optional)
    |--------------------------------------------------------------------------
    */

    // public function skpd()
    // {
    //     return $this->belongsTo(Skpd::class, 'id_skpd');
    // }

    // public function struktur()
    // {
    //     return $this->belongsTo(Struktur::class, 'id_struktur');
    // }
}
