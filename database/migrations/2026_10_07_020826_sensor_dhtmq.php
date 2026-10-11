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
        Schema::create('sensor_dhtmq', function (Blueprint $table) {
            $table->id();
            $table->decimal('suhu', 5, 1);                 // °C
            $table->decimal('kelembaban', 5, 1);           // %
            $table->decimal('gas', 8, 2)->default(0);      // ppm (MQ-135)
            $table->decimal('co2', 8, 2)->default(0);
            $table->decimal('amonia', 8, 2)->default(0);   // NH3
            $table->decimal('benzena', 8, 2)->default(0);
            $table->decimal('alkohol', 8, 2)->default(0);
            $table->decimal('asap', 8, 2)->default(0);
            $table->timestamp('waktu')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_dhtmq');
    }
};
