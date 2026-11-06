<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parcel_weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('parcel_id'); // No foreign key constraint
            $table->timestamp('forecast_time');
            $table->decimal('ambient_temperature', 5, 2)->nullable();
            $table->integer('ambient_humidity')->nullable();
            $table->decimal('wind_speed', 5, 2)->nullable();
            $table->integer('wind_direction')->nullable();
            $table->decimal('precipitation', 5, 2)->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate forecasts for same parcel/time
            $table->unique(['parcel_id', 'forecast_time']);

            // Index for efficient querying
            $table->index(['parcel_id', 'forecast_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_weather_forecasts');
    }
};
