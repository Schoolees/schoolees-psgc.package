<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','region_code'];

    public function getTable(): string
    {
        return (string) config('psgc.tables.provinces', 'provinces');
    }

    public function getSearchable(): array
    {
        return [
            'query'      => ['code','region_code'],
            'query_like' => ['name'],
        ];
    }
}
