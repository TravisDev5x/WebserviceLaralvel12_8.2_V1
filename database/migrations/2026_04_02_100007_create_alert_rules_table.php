<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('condition_type', 50);
            $table->integer('threshold')->default(1);
            $table->integer('time_window_minutes')->default(60);
            $table->string('notify_email', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('cooldown_minutes')->default(60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
