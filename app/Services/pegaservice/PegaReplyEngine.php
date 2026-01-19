<?php
namespace App\Services\pegaservice;

include_once __DIR__.'/pegaservice/vendor/autoload.php';

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\Facades\Log;
use Pegaservice\Homework\service\TrackService;

class PegaReplyEngine implements ReplyEngine{
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage)
    {
        if($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT){
            $text = $lineMessage->getMessageText();
            $text = trim($text);
            Log::info($text);
            //如果内容开头为pega 则进行处理；
            $checkResult = $this->checkAndReturnParams($text);
            if($checkResult !== false){
                // 默认是 教程；这里最后说；
                //
                // pega --action=dbInit
                // pega --action=queryUserByDateLimit --status=A --dateStart=2025/09/01 --dateEnd=2025/09/02
                // $action = 获取字符串中 --action= 后面的内容
                $action = $checkResult['action'] ?? null;

                Log::info($action);
                switch($action) {
                    case 'dbInit':
                        $this->getTrackService()->serviceInit();
                        $lineReplyMessage->appendText("Pega Service 数据库初始化示例已执行完毕。");
                        return true;
                        break;
                    case 'queryUserByDateLimit':
                        $status = $checkResult['status'] ?? null;
                        $dateStart = $checkResult['dateStart'] ?? null;
                        $dateEnd = $checkResult['dateEnd'] ?? null; // 注意这里之前你写成 dateEnd5，改为 dateEnd

                        try {
                            $results = $this->getTrackService()->queryUserByDateLimit($dateStart, $dateEnd, $status);
                        } catch (\Exception $e) {
                            $lineReplyMessage->appendText("查询失败：" . $e->getMessage());
                            return true;
                        }

                        if($results){
                            $text = "查询结果如下：\n";
                            foreach($results as $r){
                                $text .= "用户ID：" . $r->id . " 用户名：" . $r->user_name . "\n";
                            }
                            $lineReplyMessage->appendText($text);
                        } else {
                            $lineReplyMessage->appendText("查询无结果，请确认参数是否正确。");
                        }
                        return true;

                    default:
                        $lineReplyMessage->appendText(
                            "Pega Service 模块使用说明：\n" .
                            "1. 初始化数据库：pega --action=dbInit\n" .
                            "2. 按日期范围查询用户：pega --action=queryUserByDateLimit --status=A --dateStart=2025/09/01 --dateEnd=2025/09/02"
                        );
                        return true;
                }
            }
        }

        return false;;
    }

    protected $trackService;
    public function getTrackService()
    {
        $nowDb = TrackService::getBaseDb();
        if (empty($nowDb) || !($nowDb instanceof PegaDb)) {
            TrackService::setBaseDb(new PegaDb());
        }

        if(!$this->trackService){
            $this->trackService = new TrackService();
        }

        return $this->trackService;
    }

    /**
     * todo: 后面衍生一种叫命令回复模式；核心是，text内容，开始的字母全匹配，即进入对应的模块；优先级应优先于模糊匹配；然后针对命令回复参数匹配相关做参数匹配优化策略；
     */
    static public function checkAndReturnParams($text){
        // 检查开头是否是 "pega"（不区分大小写）
        if (strtolower(substr($text, 0, 4)) === 'pega') {
            $params = [];
            // 去掉开头 "pega" 和可能的空格
            $rest = trim(substr($text, 4));
            if ($rest !== '') {
                $parts = explode(' ', $rest); // 按空格分割
                foreach ($parts as $part) {
                    $part = trim($part);
                    $part = preg_replace('/[—–−]/u', '--', $part, 1);
                    if (str_starts_with($part, '--')) {
                        $part = substr($part, 2); // 去掉开头 "--"
                        $kv = explode('=', $part, 2); // 拆 key=value
                        if (count($kv) === 2) {
                            $params[$kv[0]] = $kv[1];
                        } else {
                            $params[$kv[0]] = true; // 没有等号的直接标记为 true
                        }
                    }
                }
            }

            return $params;
        }

        return false;
    }

}