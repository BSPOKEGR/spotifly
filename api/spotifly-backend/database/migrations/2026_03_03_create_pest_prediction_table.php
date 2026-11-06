<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pest_predictions', function (Blueprint $table) {
            $table->id();
            $table->uuid('parcel_id');
            $table->json('response_data');
            $table->timestamp('fetched_at');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('parcel_id');
            $table->index('fetched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pest_predictions');
    }
};
