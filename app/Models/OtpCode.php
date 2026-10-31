<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|Carbon $expire_le
 * @property int $tentatives
 */
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
