<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','city_code'];

    public function getTable(): string
    {
        return (string) config('psgc.tables.barangays', 'barangays');
    }

    public function getSearchable(): array
    {
        return [
            'query'      => ['code','city_code'],
            'query_like' => ['name'],
        ];
    }
}
