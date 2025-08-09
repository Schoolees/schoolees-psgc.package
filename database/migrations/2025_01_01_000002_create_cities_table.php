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
            $table->string('province_code')->index();
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
