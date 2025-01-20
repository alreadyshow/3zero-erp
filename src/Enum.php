<?php

namespace Erp;

class Enum {
    const SUCCESS = '0';// 成功
    const TOKEN_EXPIRED = '-9'; // token已失效
    const TOKEN_UNAUTHORIZED = '-8';// token未授权本接口
    const ILLEGAL_ACCESS = '-7';// 非法访问
    const INVALID_TOKEN_INFO = '-6';// token信息无效
    const TOKEN_MISMATCH = '-5';// token与系统不匹配
}