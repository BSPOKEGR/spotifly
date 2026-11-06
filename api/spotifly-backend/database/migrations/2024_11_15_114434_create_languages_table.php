<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('locale');
            $table->timestamps();
        });

        $now = Carbon::now();
        DB::table('languages')->insert([
            ['id' => 1, 'name' => 'greek', 'locale' => 'el', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'english', 'locale' => 'en', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
