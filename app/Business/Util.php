<?php

declare(strict_types=1);

namespace App\Business;

use Ramsey\Uuid\Uuid;

/**
 * Some utilities.
 */
class Util
{
    /** @var string */
    const KEY_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * 创建指定长度的随机 Key
     */
    public static function makeKey(int $length = 8): string
    {
        $max    = strlen(self::KEY_CHARS) - 1;
        $result = '';
        for ($i = 0; $i < $length; ++$i) {
            $result .= self::KEY_CHARS[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * 构建随机的 UUID4
     */
    public static function makeUuid4(): string
    {
        return strval(Uuid::uuid4());
    }

    /**
     * 一个增强的 boolval
     *
     * @param mixed $value
     */
    public static function boolean($value): bool
    {
        if (!boolval($value)) {
            return false;
        }
        $negatives = ['0', 'false', 'f', 'off', 'no', 'nil', 'n', 'nul', 'null', 'none'];
        if (is_string($value) && in_array(strtolower($value), $negatives)) {
            return false;
        }

        return true;
    }

    /**
     * 检查一个 URL 是不是看起来正确
     *
     * 成功时无返回，失败返回 [status, message] 数组
     */
    public static function checkUrl(string $value)
    {
        $parsed = parse_url($value);
        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https', 'ftp'])) {
            return [400, 'Invalid URL scheme.'];
        }
        $host = $parsed['host'] ?? '';
        if (empty($host)) {
            return [400, 'Host not found.'];
        }
    }

    /**
     * AES-256-CBC 解密
     *
     * @param string $text Base64 编码的任意字符串
     */
    public static function decrypt(string $text, string $identity, string $credential): string
    {
        // 注意 $text 是 Base64 的，但无需解码。否则 $options 参数需要修改
        return openssl_decrypt($text, 'aes-256-cbc', $credential, 0, $identity);
    }

    /**
     * AES-256-CBC 加密
     *
     * @return string Base64 编码的任意字符串
     */
    public static function encrypt(string $text, string $identity, string $credential): string
    {
        // 注意返回值已经采用 Base64 了，无需再次编码
        return openssl_encrypt($text, 'aes-256-cbc', $credential, 0, $identity);
    }

    /**
     * SHA256 哈希
     */
    public static function digest(string $text): string
    {
        return hash('sha256', $text);
    }
}
