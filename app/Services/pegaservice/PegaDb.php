<?php
namespace App\Services\pegaservice;

use Illuminate\Support\Facades\DB;
use Pegaservice\Homework\service\BaseDb;

class PegaDb extends BaseDb{
    public function fetchRow(string $sql, array $params = []): ?array
    {
        $result = DB::select($sql,$params);
        return (array) $result[0];  // 强制转换 stdClass -> array
    }
    public function fetchAll(string $sql, array $params = []): array
    {        
        $result = DB::select($sql, $params);
        // 把每个 stdClass 转为数组
        return array_map(fn($row) => (array) $row, $result);
    }

    public function execute(string $sql, array $params = []): int
    {
        return DB::affectingStatement($sql, $params);
    }
}