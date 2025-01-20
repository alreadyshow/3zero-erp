<?php

namespace Erp;

class RequestManager {
    private $tokenManager;

    protected $domain;

    public function __construct($domain, TokenManager $tokenManager) {
        // 初始化TokenManager实例
        $this->tokenManager = $tokenManager;
        $this->domain = $domain;
        $this->tokenManager->set_domain($domain);
    }

    private function sendCurlRequest($url, $data) {
        // 初始化cURL会话
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'token: ' . $this->tokenManager->getToken(),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function sendRequest($url, $data) {
        $url = $this->domain.$url;
        // 发送请求
        $response = $this->sendCurlRequest($url, $data);

        // 解析响应数据
        $responseData = json_decode($response, true);
        if ($responseData['resultCode'] == Enum::TOKEN_EXPIRED) {
            // 如果token过期，刷新token并重试请求
            $this->tokenManager->refreshToken();
            return $this->sendRequest($url, $data);
        } elseif ($responseData['resultCode'] != Enum::SUCCESS) {
            // 如果请求失败，抛出异常
            throw new \Exception('Request failed with result code: ' . $responseData['resultCode'] . ' - ' . $responseData['errorMsg']);
        }

        // 返回成功响应
        return $response;
    }
}