<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('psgc.tables.cities', 'cities'), function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('name');
            $table->string('region_code')->index();
            // HUC/ICC can be province-independent
            $table->string('province_code')->nullable()->index();
            $table->boolean('is_city')->default(true)->index();
            $table->string('city_class')->nullable()->index();
            $table->timestamps();

            $table->index(['region_code', 'province_code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('psgc.tables.cities', 'cities'));
    }
};
