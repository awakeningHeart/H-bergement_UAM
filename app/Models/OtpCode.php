<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    public $timestamps = false;

    protected $table = 'otp_codes';

    protected $guarded = [];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return ['expire_le' => 'datetime', 'envoye_le' => 'datetime', 'utilise_le' => 'datetime', 'tentatives' => 'integer'];
    }
}
