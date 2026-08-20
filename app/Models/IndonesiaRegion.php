<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndonesiaRegion extends Model
{
    public const PROVINCE = 1;

    public const REGENCY = 2;

    public const DISTRICT = 3;

    public const VILLAGE = 4;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    protected $fillable = ['code', 'parent_code', 'level', 'name', 'postal_code'];

    protected function casts(): array
    {
        return ['level' => 'integer'];
    }
}
