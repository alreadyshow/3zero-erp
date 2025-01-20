<?php

namespace Erp;

class TokenManager {
    private $cache_funcs;
    private $tokenUrl;
    private $sysKey;
    private $secret;
    public $token_key = 'erp_token';
    private $domian;

    public function set_domain($domain)
    {
        $this->domian = $domain;
    }

    /**
     * 构造函数：用于初始化获取token所需的URL、密钥和密钥，以及缓存功能
     * 
     * @param string $tokenUrl 获取token的URL
     * @param string $sysKey 系统密钥，用于加密解密token
     * @param string $secret 密钥，用于验证token的合法性
     * @param array $cache_funcs 可选参数，包含缓存功能的数组，默认为空数组
     */
    public function __construct($tokenUrl, $sysKey, $secret, $cache_funcs = []) {
        // 初始化缓存、tokenUrl、sysKey和secret
        $this->cache_funcs = $cache_funcs;
        $this->tokenUrl = $tokenUrl;
        $this->sysKey = $sysKey;
        $this->secret = $secret;
    }

    public function getToken() {
        if ($this->cache_funcs) {
            // 从缓存中获取token
            if ($token = call_user_func($this->cache_funcs['get'], $this->token_key)) {
                return $token;
            }
        }
        
        // 如果缓存中没有token或token过期，请求新的token
        $token = $this->requestToken();
        if ($this->cache_funcs) {
            call_user_func($this->cache_funcs['set'], $this->token_key, $token);
            // $this->cache_funcs->set($this->token_key, $token);
        }
        return $token;
    }

    // 请求新的token
    private function requestToken() {
        // 初始化cURL会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->domian.$this->tokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'sysKey' => $this->sysKey,
            'secret' => $this->secret
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        curl_close($ch);

        // 解析响应数据
        $responseData = json_decode($response, true);
        if ($responseData['resultCode'] == '0') {
            return $responseData['data'];
        } else {
            // 如果请求token失败，抛出异常
            throw new \Exception('Failed to get token: ' . $responseData['errorMsg']);
        }
    }

    /**
     * 本函数首先调用requestToken方法请求新的token，然后根据cache_funcs属性判断是否需要缓存token
     * 如果需要缓存，则使用cache_funcs中的set方法更新缓存中的token
     * 最后，将新获取的token返回给调用者
     * 
     * @return mixed 刷新后的token
     */
    public function refreshToken() {
        // 请求新的token
        $token = $this->requestToken();
        // 如果缓存功能已启用，更新缓存中的token
        if ($this->cache_funcs) {
            call_user_func($this->cache_funcs['set'], $this->token_key, $token);
        }
        // 返回新获取的token
        return $token;
    }
}