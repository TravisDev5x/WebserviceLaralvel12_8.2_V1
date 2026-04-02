<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_filters', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 20);
            $table->string('event_type', 50);
            $table->string('filter_field', 100);
            $table->string('filter_operator', 20);
            $table->string('filter_value', 255)->nullable();
            $table->string('action', 20)->default('process');
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_filters');
    }
};
