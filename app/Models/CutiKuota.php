<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CutiKuota extends Model
{
    protected $fillable = ['jenis', 'kuota', 'carry_over', 'aktif'];

    protected $casts = [
        'kuota' => 'integer',
        'carry_over' => 'boolean',
        'aktif'      => 'boolean',
    ];
}
