<?php

namespace Erp;

class TokenManager {
    /** @var Psr/CacheInterface */
    private $cache;
    private $tokenUrl;
    private $sysKey;
    private $secret;
    public $token_key = 'erp_token';
    private $domian;

    public function set_domain($domain)
    {
        $this->domian = $domain;
    }

    public function __construct($cache, $tokenUrl, $sysKey, $secret) {
        // 初始化缓存、tokenUrl、sysKey和secret
        $this->cache = $cache;
        $this->tokenUrl = $tokenUrl;
        $this->sysKey = $sysKey;
        $this->secret = $secret;
    }

    public function getToken() {
        // 从缓存中获取token
        $token = $this->cache->get($this->token_key);
        if ($token && !$this->isTokenExpired($token)) {
            return $token;
        }

        // 如果缓存中没有token或token过期，请求新的token
        $token = $this->requestToken();
        $this->cache->set($this->token_key, $token);
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

    // 检查token是否过期
    private function isTokenExpired($token) {
        // 假设token包含过期时间信息，这里需要根据实际情况实现
        // 例如：检查token中的exp字段
        $tokenData = json_decode(base64_decode(explode('.', $token)[1]), true);
        return time() > $tokenData['exp'];
    }

    // 检查token是否过期并刷新
    public function refreshToken() {
        // 请求新的token
        $token = $this->requestToken();
        $this->cache->set($this->token_key, $token);
        return $token;
    }
}