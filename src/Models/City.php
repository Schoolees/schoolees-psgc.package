<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','region_code','province_code','is_city','city_class'];

    public function getSearchable(): array
    {
        return [
            'query'      => ['code','region_code','province_code','is_city'],
            'query_like' => ['name','city_class'],
        ];
    }
}
