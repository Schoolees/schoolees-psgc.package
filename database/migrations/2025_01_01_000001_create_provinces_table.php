<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('psgc.tables.provinces', 'provinces'), function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name');
            $table->string('region_code', 20)->index();
            $table->timestamps();

            $table->index(['region_code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('psgc.tables.provinces', 'provinces'));
    }
};
