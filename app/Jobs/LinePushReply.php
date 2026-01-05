<?php

namespace App\Jobs;

use App\Services\Laravel\CoreEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LinePushReply implements ShouldQueue
{
    use Queueable;

    protected $message = null;
    protected $wid = '';
    /**
     * Create a new job instance.
     */
    public function __construct(array $message)
    {
        //
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(CoreEngine $coreEngine): void
    {
        //
        Log::info('--------job start-------->');
        $coreEngine->jobHandle($this->message);
    }
}
