<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['native', 'external'])->default('native');
            $table->string('external_provider')->nullable();
            $table->string('external_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->unsignedSmallInteger('party_size');
            $table->dateTime('reserved_for');
            $table->foreignId('cafe_table_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'seated', 'cancelled', 'no_show'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['external_provider', 'external_id']);
            $table->index('reserved_for');
        });

        // config is stored encrypted (encrypted:array cast), so it is text, not json.
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->boolean('is_enabled')->default(false);
            $table->text('config')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('reservations');
    }
};
