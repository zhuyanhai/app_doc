<?php

namespace App\Services\User\Controllers;

use App\Services\ServiceAbstract;
use App\Services\User\Helpers\LoginChecked;
use App\Services\User\Models\SsoTicketLogModel;
use App\Services\User\Models\UserModel;
use App\Services\User\Helpers\LoginToken;
use App\Services\User\Helpers\User;

/**
 * 用户登录服务
 *
 * 版本号：v1
 *
 * Class DoLoginV1
 * @package App\Services\User\Controllers;
 */
class DoLoginV1 extends ServiceAbstract
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
            'account'  => 'required',
            'password' => 'required',
        ], [
            'account.required'  => '请输入手机号',
            'password.required' => '请输入密码',
        ]);
    }

    /**
     * 服务必须实现的方法，因为调用服务会自动调用本方法
     *
     * @return array
     */
    public function run()
    {
        $userModel = UserModel::where('phone', $this->_params['account'])->first();
        if (!$userModel) {
            $this->error('帐号不存在，请先注册！');
        }
        if (intval($userModel->frozen) !== 1) {
            if ($userModel->password == md5($this->_params['password'])) {

                //VOIP
                //$iModel = new \Common\Model\IcallModel();
                //$iUid = $iModel->phoneGetUid($phone);
                //$udata = $this->user($userModel->uid);
                //$udata['data']['iuid'] = $iUid;

                $result = callService('user.getInfoV1', ['userid' => $userModel->uid]);

                if (intval($result['code']) != 0) {
                    $this->error($result['msg']);
                }

                //临时
                $result['data']['iuid'] = 0;

                //用户登录token
                $result['data']['token'] = LoginToken::build($userModel->uid, $userModel->phone, $userModel->password, $userModel->createtime);

                //sso_ticket - 生成单点登陆的sso_ticket
                $result['data']['ssoTicket'] = LoginToken::SsoTicket($userModel->uid,$userModel->password);
                //设置sso_ticket到用户数据表
                $userModel->sso_ticket = $result['data']['ssoTicket'];
                $userModel->save();

                //记录用户登陆票据log
                $sso_ticket = new SsoTicketLogModel();
                $sso_ticket->sso_ticket = $userModel->sso_ticket;//票据
                $sso_ticket->userid = $userModel->uid;//用户id
                $sso_ticket->login_at = time();//登陆时间
                $sso_ticket->system = $this->_params['_apiHeaders']['hst-system'][0];//系统
                $sso_ticket->save();

                //设置用户信息到openVpn
                User::setInfoToOpenVpn($userModel->uid, $result['data']['token'], $userModel->traffic_patterns, $userModel->allow_external_updates, $this->_params['_apiHeaders']);

                //是否允许登陆，允许1＝代表审核通过 不允许0＝代表审核没有通过
                if ($result['data']['isRegisterCheck'] < 2) {//<2 审核未通过 或 待审核
                    if($result['data']['isRegisterCheck'] < 1) {
                        $checkStatus = 0;//审核不通过
                    } elseif ($result['data']['personFrontPic'] == '' || $result['data']['personBackPic'] == '') {
                        $checkStatus = 1;//待审核
                    } else {
                        $checkStatus = 2;//审核中
                    }
                    return $this->response([
                        'uid'         => $result['data']['uid'],
                        'token'       => $result['data']['token'],
                        'checkStatus' => $checkStatus,
                    ], $result['cookies']);
                } else {
                    $result['data']['checkStatus'] = 3;//
                }

                return $this->response($result['data'], $result['cookies']);
            }else {
                $this->error('密码错误');
            }
        } else {
            $this->error('该用户已被冻结');
        }
    }
}
