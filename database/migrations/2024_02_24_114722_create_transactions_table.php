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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_sender_id')->constrained('users');
            $table->foreignId('user_receiver_id')->nullable()->constrained('users');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->float('value', 8, 2);
            $table->boolean('give_up')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
