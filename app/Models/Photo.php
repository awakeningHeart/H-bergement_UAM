<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    protected $table = 'photos';

    protected $guarded = [];

    public function url(): string
    {
        return Storage::disk('public')->url($this->chemin);
    }

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}
