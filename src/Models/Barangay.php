<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $table = 'barangays';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','city_code'];

    public function getSearchable(): array
    {
        return [
            'query'      => ['code','city_code'],
            'query_like' => ['name'],
        ];
    }
}
