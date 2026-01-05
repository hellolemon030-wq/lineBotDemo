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
        Schema::create('msg', function (Blueprint $table) {
            $table->id();
            // belong
            $table->string('bot_id', 64)->comment('  Bot ID');
            $table->string('source_type', 64)->comment('source type');
            $table->string('user_id', 64)->comment('user ID');
            $table->string('event_id', 64)->default('')->comment('webhookEventId/ uniqueid');
            $table->string('event_type', 64)->default('')->comment('event type');

            $table->string('platform_message_id')->nullable()->comment(' LINE messageId');

            //0=handle，1=push
            $table->smallInteger('direction')->comment('0=handle 1=push');
            $table->string('reply_token')->comment('replyToken');

            //message type
            $table->string('message_type', 32)->comment('text/image/sticker/location/system');

            //content detail;
            $table->text('content')->nullable()->comment('content');
            $table->bigInteger('timestamp')->default(0);

            // raw
            $table->json('raw_payload')->nullable()->comment('raw');

            // status
            $table->smallInteger('status')->default(0)->comment('0=pending 1=sent 2=failed');

            // isreaded
            $table->smallInteger('is_read')->default(0)->comment('0=unreaded 1=readed');

            $table->timestamps();

            // index
            $table->index(['bot_id', 'user_id']);
            $table->index('platform_message_id');
            $table->index(['bot_id','direction','event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('msg');
    }
};
