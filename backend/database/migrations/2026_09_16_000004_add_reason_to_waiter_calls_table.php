<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The customer UI offers four call reasons (garson, su & peçete, hesap, temizlik); keep the text.
    public function up(): void
    {
        Schema::table('waiter_calls', function (Blueprint $table) {
            $table->string('reason', 100)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('waiter_calls', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
