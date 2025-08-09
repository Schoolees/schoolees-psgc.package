<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('psgc.tables.regions', 'regions'), function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('psgc.tables.regions', 'regions'));
    }
};
