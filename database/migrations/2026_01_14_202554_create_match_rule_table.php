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
        Schema::create('match_rule', function (Blueprint $table) {
            $table->id();

            $table->string('bot_id')->index();
            $table->string('keywords');
            $table->string('match_type', 16);
            $table->string('reply_type', 16);

            $table->text('reply_content')->nullable();

            $table->string('module_name')->nullable();
            $table->text('module_call_params')->nullable();

            $table->timestamps();

            $table->unique(['bot_id', 'match_type', 'keywords']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_rule');
    }
};
