<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_menu_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route_name');
            $table->string('label');
            $table->string('icon', 64)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'route_name']);
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_menu_pins');
    }
};
