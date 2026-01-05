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
        Schema::table('bot_models', function (Blueprint $table) {
            //
            // 记录 token 的过期时间
            $table->integer('token_expire_at')->default(0)->after('access_token');
            $table->integer('token_refresh_count')->default(0)->after('token_expire_at');
            $table->text('apimsg')->nullable()->after('token_refresh_count');
            $table->smallInteger('need_manual_update')->default(0)->after('apimsg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bot_models', function (Blueprint $table) {
            //
            $table->dropColumn('token_expire_at');
            $table->dropColumn('token_refresh_count');
            $table->dropColumn('apimsg');
            $table->dropColumn('need_manual_update');
        });
    }
};
