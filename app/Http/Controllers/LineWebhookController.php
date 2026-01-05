<?php

namespace App\Http\Controllers;

use App\Services\Laravel\CoreEngine;
use App\Services\Laravel\LineBotServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineWebhookController extends Controller
{
    public CoreEngine $coreEngine;
    
    public function __construct(CoreEngine $coreEngine)
    {
        $this->coreEngine = $coreEngine;
        Log::info('--------controller start-------->');
    }

    public function webhook(Request $request, ?string $botKey = null)
    {
        $botKey = $botKey ?? '';
        $this->coreEngine->httpHandle($request,$botKey);
        return;
    }

    public function webhook2(Request $request){
        echo "webhook2 test ok;";
        return;
    }
}