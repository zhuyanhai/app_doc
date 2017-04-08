<?php

namespace App\Services\User\Controllers;

use Illuminate\Support\Facades\DB;
use App\Services\ServiceAbstract;
use App\Services\User\Models\UserModel;
use App\Services\User\Models\RegistModel;
use App\Services\User\Models\AccountModel;
use App\Services\User\Models\UserRegisterCheckModel;
use App\Services\User\Helpers\LoginToken;
use App\Services\User\Helpers\User;

/**
 * 用户注册服务
 *
 * 版本号：v1
 *
 * Class DoRegisterV1
 * @package App\Services\User\Controllers;
 */
class DoRegisterV1 extends ServiceAbstract
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
            'account' => 'required',//登录帐号
            'password' => 'required',//登录密码
//            'vcode' => 'required',//校验码
//            'code' => 'required',//手机验证码
//            'pv' => 'required',//系统类型 android
//            'personid' => 'required',//身份证号
//            'personFrontPic' => 'required',//身份证正面图片
//            'personBackPic' => 'required',//身份证背面图片
        ], [
            'account.required' => '请输入手机号',
            'password.required' => '请输入密码',
//            'vcode.required' => '参数错误',
//            'code.required' => '请输入验证码',
//            'personid.required' => '请输入身份证号',
//            'pv.required' => '参数错误',
//            'personFrontPic.required' => '请上传身份证正面照片',
//            'personBackPic.required' => '请上传身份证背面照片',
        ]);
    }

    /**
     * 服务必须实现的方法，因为调用服务会自动调用本方法
     *
     * @return array
     */
    public function run()
    {
        //验证码 allen 本次注释掉，下个版本再考虑使用
        //$result = callService('foundation.checkMobileCodeV1', ['account'=>$this->params['account'], 'code' => $this->params['code']]);
        //if ($result['code'] != 0) {
            //$this->error($result['msg']); 临时注释
        //}

        $data['phone'] = trim($this->_params['account']);
        $data['password'] = trim($this->_params['password']);
	    //$data['personFrontPic'] = trim($this->_params['personFrontPic']);
	    //$data['personBackPic'] = trim($this->_params['personBackPic']);

        //allen 下个版本再考虑强制填写
        //$data['personid'] = (isset($this->params['personid']))?strtolower($this->params['personid']):'';

        $data['createtime'] = time();
        $data['openfire'] = rand(100000, 999999);

        //16.07.12 只允许特定号段手机号注册
        if (strlen($data['phone']) == 11 && !$this->_checkRegistLimit($data['phone'])) {
            //$this->error('该手机号段无法注册');
        }

        //检测身份证号
/*        if (!empty($data['personid'])) {
            if (!preg_match("/^[1-9][0-9]{16}[0-9x]$/", $data['personid'])) {
                $this->error('身份证号码不正确');
            }
        }
*/
        //检测手机号
        if (UserModel::where('phone', $data['phone'])->first()) {
            $this->error('该手机号已注册');
        }

        //检测密码格式
        if (!preg_match("/^[0-9a-zA-Z]{6,15}$/", $data['password'])) {
            $this->error('密码由字母或数字组成长度6-15位');
        }

        $pwdForIcall = $data['password'];
        $data['password'] = md5($data['password']);

        //$openfire = new Openfire();
        //$iMod = new IcallModel(); todo 没有apps库


        DB::beginTransaction();

        //插入信息到用户表
        $userModel = new UserModel();
        $userModel->phone = $data['phone'];
        $userModel->password = $data['password'];
        $userModel->personid = '';
        $userModel->createtime = $data['createtime'];
        $userModel->openfire = $data['openfire'];
        $userModel->is_register_check = 1;
        $flag1 = $userModel->save();

        //插入信息到账户表
        $accountModel = new AccountModel();
        $accountModel->uid = $userModel->uid;
        $flag2 = $accountModel->save();

        if (!$flag1 || !$flag2) {
            DB::rollBack();
            $this->error('注册失败');
        }

        //注册Openfire
        $openfireResult = callService('foundation.doOpenfireV1', [
            'who'    => 'openfire',
            'action' => 'registerUser',
            'data'   => ['username' => $userModel->uid, 'password' => $data['openfire']]
        ]);

        if ($openfireResult['code'] != 0 || $openfireResult['data']['result'] != 1) {
            DB::rollBack();
            callService('foundation.doOpenfireV1', [
                'who'    => 'openfire',
                'action' => 'deleteUser',
                'data'   => ['username' => $userModel->uid]
            ]);
            $this->error('openFire注册失败');
        }

        //注册icall 第二版再加上
        $iCallResult = callService('foundation.doICallV1', [
            'action' => 'registerUser',
            'data'   => [
                'phone'      => $userModel->phone,
                'password'   => $userModel->password,
                'systemType' => $this->_params['_apiHeaders']['hst-system'][0],
            ]
        ]);

        if ($iCallResult['code'] != 0 || $iCallResult['data']['result'] != 0) {
            DB::rollBack();
            callService('foundation.doICallV1', [
                'action' => 'deleteUser',
                'data'   => [
                    'phone' => $userModel->phone,
                ]
            ]);
            callService('foundation.doOpenfireV1', [
                'who'    => 'openfire',
                'action' => 'deleteUser',
                'data'   => ['username' => $userModel->uid]
            ]);
            $this->error('voip注册失败');
        }

        //插入信息到审核队列
        $userRegisterCheckModel = new UserRegisterCheckModel();
        $userRegisterCheckModel->uid = $userModel->uid;
        $userRegisterCheckModel->status = 1;
        $userRegisterCheckModel->created_at = time();
        $flag3 = $userRegisterCheckModel->save();

        if (!$flag3) {
            DB::rollBack();
            $this->error('注册失败');
        }

        DB::commit();

        $token = LoginToken::build($userModel->uid, $userModel->phone, $userModel->password, $userModel->createtime);

        return $this->response([
            'uid'   => $userModel->uid,
            'token' => $token,
        ]);
    }

    /**
     * 检测只允许特定号段手机号注册
     *
     * @param $account 用户登录帐号
     * @return bool
     */
    private function _checkRegistLimit($account)
    {
	return true;
        $registModel = RegistModel::first();
        $limit = $registModel->num;
        if ($limit) {
            $limits = explode(';',$limit);
            for($i = 0, $total = count($limits); $i < $total; $i++){
                if (preg_match("/^".$limits[$i]."/", $account)) {
                    return true;
                }
            }
        }
    }
}
