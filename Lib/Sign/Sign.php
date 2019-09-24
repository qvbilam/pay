<?php
/**
 * Created by PhpStorm.
 * User: qvbilam
 * Date: 2019-08-26
 * Time: 16:54
 */

namespace App\Lib\Sign;
class Sign
{
    // 加密
    static public function getSign($key, $data)
    {
        if (empty($data)) {
            return '';
        } else {
            ksort($data);
            $preArr = array_merge($data, ['key' => $key]);
            $preStr = http_build_query($preArr);
            return md5($preStr);
        }
    }

    static public function ts_time($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }

        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000);

        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    static public function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectimes = substr($msectime, 0, 13);
    }
}