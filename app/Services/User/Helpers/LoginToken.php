<?php

namespace App\Services\User\Helpers;

use App\Libraries\Utils;

/**
 * 用户登录token 生成、检测
 *
 * Class LoginToken
 * @package App\Services\User\Helpers;
 */
class LoginToken
{
    /**
     * token 综合秘钥
     */
    const SECRET_KEY = '[1wscu8*654fv.]';

    /**
     * 生成用户登录token
     *
     * @param int $userid 用户ID
     * @param string $account 用户账号
     * @param string $password 用户密码
     * @param int $createtime 用户被创建时间
     * @return string
     */
    public static function build($userid, $account, $password, $createtime)
    {
        $useridStr = Utils\Mid::id2URL($userid);
        return strtoupper(md5(md5(self::SECRET_KEY . $userid . $account) . $password . $createtime)).$useridStr;
    }

    /**
     * 用户登录token 校验
     *
     * @param string $checkToken 待检查的登录TOKEN
     * @param int $userid 用户ID
     * @param string $account 用户账号
     * @param string $password 用户密码
     * @param int $createtime 用户被创建时间
     * @return bool true=匹配 false=不匹配
     */
    public static function check($checkToken, $userid, $account, $password, $createtime)
    {
        $token = self::build($userid, $account, $password, $createtime);
        if ($token == $checkToken) {
            return true;
        }
        return false;
    }

    /**
     * 返回从token中剥离出来的用户id
     *
     * @param $token
     * @return int
     */
    public static function stripUserid($token)
    {
        $useridStr = mb_substr($token, 32);
        $userid = Utils\Mid::url2ID($useridStr);
        return $userid;
    }

    /**
     * 生成用户登录sso_ticket
     *
     * @param int $userid 用户ID
     * @param string $password 用户密码
     * @return string
     */
    public static function SsoTicket($userid, $password)
    {
        return str_shuffle(strtoupper(md5(md5($userid) . $password)) . (time() * mt_rand(0,999)));
    }

}