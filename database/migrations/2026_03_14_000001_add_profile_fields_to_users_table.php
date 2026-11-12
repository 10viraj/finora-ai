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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('individual')->after('email'); // individual, small_business, admin
            $table->string('business_name')->nullable()->after('role');
            $table->string('currency', 3)->default('USD')->after('business_name');
            $table->string('phone')->nullable()->after('currency');
            $table->string('ai_provider')->default('openai')->after('phone'); // openai, custom
            $table->string('ai_api_key')->nullable()->after('ai_provider');
            $table->string('ai_model')->default('gpt-4o-mini')->after('ai_api_key');
            $table->string('ai_base_url')->nullable()->after('ai_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'business_name',
                'currency',
                'phone',
                'ai_provider',
                'ai_api_key',
                'ai_model',
                'ai_base_url',
            ]);
        });
    }
};
