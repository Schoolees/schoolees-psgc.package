<?php

namespace Schoolees\Psgc\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','short_name'];

    public function getTable(): string
    {
        return (string) config('psgc.tables.regions', 'regions');
    }

    public function getSearchable(): array
    {
        return [
            'query'      => ['code'],
            'query_like' => ['name', 'short_name'],
        ];
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class, 'region_code', 'code');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'region_code', 'code');
    }
}
