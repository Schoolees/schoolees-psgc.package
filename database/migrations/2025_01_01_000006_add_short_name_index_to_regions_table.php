<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `short_name` is sortable (RegionService::getRegions allows it) but was the
 * only allow-listed sort column on any table without an index behind it.
 *
 * The table holds 18 rows, so this buys no measurable time. It is here for
 * consistency: `regions.name` is already indexed on the same table, and leaving
 * one of two sortable text columns uncovered is the kind of asymmetry that gets
 * re-discovered by every future review.
 *
 * Added as a separate migration rather than edited into the create migration so
 * that installs which already ran that also pick it up.
 */
return new class extends Migration
{
    private function table(): string
    {
        return (string) config('psgc.tables.regions', 'regions');
    }

    public function up(): void
    {
        $table = $this->table();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'short_name')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->index('short_name', "{$table}_short_name_index");
        });
    }

    public function down(): void
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->dropIndex("{$table}_short_name_index");
        });
    }
};
