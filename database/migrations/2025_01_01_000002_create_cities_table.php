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
            // NOTE: HUC/ICC cities have no province -> must be nullable
            $table->string('province_code')->nullable()->index();
            $table->boolean('is_city')->default(true);
            $table->string('city_class')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('psgc.tables.cities', 'cities'));
    }
};
