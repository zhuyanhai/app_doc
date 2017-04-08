<?php

namespace App\Services\User\Controllers;

use App\Services\ServiceAbstract;
use App\Services\User\Helpers\LoginToken;
use App\Services\User\Helpers\User;
use App\Services\User\Models\SsoTicketLogModel;

/**
 * 通过token检测用户是否登录服务
 *
 * 版本号：v1
 *
 * Class CheckLoginV1
 * @package App\Services\User\Controllers;
 */
class CheckLoginV1 extends ServiceAbstract
{
    /**
     * 校验请求参数
     *
     * true = 校验通过 false=校验不通过
     * @return boolean
     */
    public function paramsValidate()
    {
        return $this->_validate($this->_params, [
            'token' => 'required|string',
        ], [
            'token.required' => '参数错误',
            'token.string' => '参数错误',
        ]);
    }

    /**
     * 服务必须实现的方法，因为调用服务会自动调用本方法
     *
     * @return array
     */
    public function run()
    {
        $userid = LoginToken::stripUserid($this->_params['token']);
        if ($userid <= 0) {
            $this->error('该帐号不存在');
        }
        $userModel = User::getBaseInfoByUserid($userid);
        if (!$userModel) {
            $this->error('该帐号不存在');
        }

        $checkResult = LoginToken::check($this->_params['token'], $userModel->uid, $userModel->phone, $userModel->password, $userModel->createtime);

        if (!$checkResult) {
            $this->error('该帐号不存在');
        }

        if (!isset($this->_params['noCheckTicket']) || $this->_params['noCheckTicket'] !== 'yes') {
            if ($userModel->sso_ticket != $this->_params['ssoTicket']) {
                //获取上次登陆的票据提供给用户
                $ssoTicket = SsoTicketLogModel::where('userid', $userid)->orderBy('id', 'desc')->first();
                $msg = "您的账号于" . date('H:i:s', $ssoTicket->login_at) . "在另一台" . $ssoTicket->system . "手机登录。如非本人操作，则密码可能已泄露，建议联系客服进行修改，客服热线：0580-5850000";
                $this->error($msg, 4001);
            }
        }

        if (intval($userModel->frozen) !== 1) {
            return $this->response($userModel->toArray());
        } else {
            $this->error('该用户已被冻结');
        }
    }
}
