<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','short_name'];

    public function getSearchable(): array
    {
        return [
            'query'      => ['code'],
            'query_like' => ['name', 'short_name'],
        ];
    }
}
