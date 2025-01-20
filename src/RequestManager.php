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

    /**
     * 发送cURL请求
     *
     * 该方法用于向指定URL发送POST请求，并携带JSON格式的数据作为请求体
     * 主要用于与外部API进行交互
     *
     * @param string $url 请求的URL地址
     * @param array $data 要发送的数据，将被转换为JSON格式
     *
     * @return mixed 返回请求的响应内容
     */
    private function sendCurlRequest($url, $data) {
        // 初始化cURL会话
        $ch = curl_init();
        // 设置请求的URL
        curl_setopt($ch, CURLOPT_URL, $url);
        // 启用POST请求
        curl_setopt($ch, CURLOPT_POST, 1);
        // 设置POST请求的数据，将其转换为JSON格式
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // 设置请求头，包含token认证和内容类型
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'token: ' . $this->tokenManager->getToken(),
            'Content-Type: application/json'
        ]);
        // 设置cURL以字符串形式返回请求结果，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // 执行cURL请求并获取响应
        $response = curl_exec($ch);
        // 关闭cURL会话
        curl_close($ch);
    
        return $response;
    }

    /**
     * 发送请求到指定URL
     *
     * 本函数负责组装完整的请求URL，并发送请求。如果请求失败或token过期，会进行相应的处理。
     * 如果请求成功，会解析响应数据并返回。
     *
     * @param string $url 请求的相对URL路径
     * @param mixed $data 请求的数据
     *
     * @return mixed 成功响应的数据
     *
     * @throws \Exception 如果请求失败或解析响应失败
     */
    public function sendRequest($url, $data) {
        // 组装完整的请求URL
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
        $res = json_decode($response, true);
    
        if ($res['resultCode'] !== "0") {
            // 如果响应结果码不为0，抛出异常
            throw new \Exception(Enum::$err_msg[$res['resultCode']] ?? '未知错误：'.$res['errorMsg']);
        }
    
        // 返回成功响应的数据
        return $res['data'];
    }
}