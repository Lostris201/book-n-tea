<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('name');
            $table->string('qr_token', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('cafe_table_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['new', 'preparing', 'ready', 'delivered', 'done'])->default('new');
            $table->string('note', 1000)->default('');
            $table->boolean('has_new_items')->default(false);
            $table->unsignedInteger('total_cents')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['cafe_table_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_snapshot');
            $table->json('options_snapshot')->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('qty');
            $table->boolean('is_new')->default(false);
            $table->timestamps();
        });

        Schema::create('waiter_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_table_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['waiter', 'bill']);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['resolved_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiter_calls');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cafe_tables');
    }
};
