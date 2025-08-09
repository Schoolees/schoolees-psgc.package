<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('psgc.tables.barangays', 'barangays'), function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('name');
            $table->string('city_code')->index();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists(config('psgc.tables.barangays', 'barangays'));
    }
};
