<?php

use \Erp\Erp;
use Erp\RequestManager;
use \Erp\TokenManager;

class Cache
{
    public function set($key, $value)
    {
        echo json_encode($key, $value);
    } 

    public function get($key)
    {
        return 'xxxx';
    }

}

$cache = new Cache();

$tokenManager = new TokenManager($cache, '/erp_intfc/token/getTokenBySecret', 'xxx', 'xxx');

$erp = new RequestManager('https://test.erp.com', $tokenManager);

$ret = $erp->sendRequest('/erp_intfc/basedata/orgGoods/findBasOrgGoodsStock', [
    'entId' => 1,
    'orgCodeList' => ['kjhj'],
    'pageable' => ['pageNumber' => 0, 'pageSize' => 10]
]);

echo json_encode($ret, 320);