<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PSGCSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seed(config('psgc.tables.regions','regions'),   'psgc/regions.json',   ['code'], ['name','short_name','updated_at']);
            $this->seed(config('psgc.tables.provinces','provinces'),'psgc/provinces.json',['code'], ['name','region_code','updated_at']);
            $this->seed(config('psgc.tables.cities','cities'),     'psgc/cities.json',    ['code'], ['name','region_code','province_code','is_city','city_class','updated_at']);
            $this->seedLarge(config('psgc.tables.barangays','barangays'),'psgc/barangays.json',['code'], ['name','city_code','updated_at']);
        });
    }

    protected function seed(string $table, string $path, array $keys, array $updates): void
    {
        $rows = $this->readJsonArray($path);

        // Normalize city rows (province_code can be null for HUC/ICC)
        if ($table === config('psgc.tables.cities', 'cities')) {
            $this->normalizeCities($rows);
        }

        $now = now();
        foreach ($rows as &$r) {
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }
        unset($r);

        DB::table($table)->upsert($rows, $keys, $updates);
    }

    protected function seedLarge(string $table, string $path, array $keys, array $updates): void
    {
        $rows = $this->readJsonArray($path);

        // If we ever use seedLarge for cities, still normalize
        if ($table === config('psgc.tables.cities', 'cities')) {
            $this->normalizeCities($rows);
        }

        $now = now();
        foreach (array_chunk($rows, 3000) as $chunk) {
            foreach ($chunk as &$r) {
                $r['created_at'] = $now;
                $r['updated_at'] = $now;
            }
            unset($r);

            DB::table($table)->upsert($chunk, $keys, $updates);
        }
    }

    /**
     * Force null province_code for HUC/ICC and empty values.
     */
    protected function normalizeCities(array &$rows): void
    {
        foreach ($rows as &$r) {
            $pc = $r['province_code'] ?? null;

            if ($pc === '' || $pc === '0') {
                $r['province_code'] = null;
            }

            if (isset($r['city_class']) && in_array($r['city_class'], ['HUC','ICC'], true)) {
                $r['province_code'] = null;
            }
        }
        unset($r);
    }

    protected function readJsonArray(string $path): array
    {
        $relativePath = ltrim($path, '/'); // e.g. "psgc/regions.json"

        $resourcePath = base_path('resources/' . $relativePath);
        if (file_exists($resourcePath)) {
            $data = json_decode(file_get_contents($resourcePath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Invalid JSON in {$resourcePath}: " . json_last_error_msg());
            }
            return $data;
        }

        if (Storage::disk('local')->exists($relativePath)) {
            $data = json_decode(Storage::disk('local')->get($relativePath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Invalid JSON in storage/app/{$relativePath}: " . json_last_error_msg());
            }
            return $data;
        }

        throw new RuntimeException("Missing file: resources/{$relativePath} or storage/app/{$relativePath}");
    }
}
