<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 请求参数
     *
     * @var array
     */
    protected $_params = [];

    /**
     * 请求参数 header
     *
     * @var array
     */
    protected $_headers = [];

    /**
     * appId
     *
     * app请求，非H5
     *
     * @var string
     */
    protected $appId;

    /**
     * appSecret
     *
     * app请求，非H5
     *
     * @var string
     */
    protected $appSecret;

    /**
     * 返回状态码
     *
     * @var int
     */
    protected $returnCode = 0;

    /**
     * 返回错误信息
     *
     * @var string
     */
    protected $returnMsg = '';

    /**
     * 定义接口必须登录才可以被访问
     *
     * @var bool true＝必须登录 false＝可以不登陆就访问
     */
    protected $foreLogin = true;

    /**
     * 当前登录用户
     *
     * @var array
     */
    protected $loginUserInfo = null;

    /**
     * 是否跨域请求
     *
     * @var boolean
     */
    protected $isCrossDomain = false;

    /**
     * Request
     *
     * @var Request
     */
    protected $_request = null;

    /**
     * 错误码
     *
     * @var array
     */
    protected $_errCodes = [
        // 系统码
        '0' => '成功',
        '400' => '未知错误',
        '403' => '无此权限',
        '500' => '服务器异常',

        //指定有意义的错误段 4000 - 4999
        '4000' => '您处于未登陆状态，请先登录！',
        '4001' => '您的账号于07:46在另一台Android手机登录。如非本人操作，则密码可能已泄露，建议联系客服进行修改，客服热线：0580-5850000',

        // 公共错误码
        '1001' => '[appId]缺失',
        '1002' => '[appId]不存在或无权限',
        '1003' => '[method]缺失',
        '1004' => '[format]错误',
        '1005' => '[sign_method]错误',
        '1006' => '[sign]缺失',
        '1007' => '[sign]签名错误',
        '1008' => '[method]方法不存在',
        '1009' => 'run方法不存在，请联系管理员',
        '1010' => '[nonce]缺失',
        '1011' => '[nonce]必须为字符串',
        '1012' => '[nonce]长度必须为1-32位',
        '1013' => '[version]缺失',
        '1014' => '[version]必须为字符串',
    ];

    /**
     * ApiController constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->_params  = $request->all();
        $this->_headers = $request->header();
        $this->_request = $request;

        //授权允许的跨域域名
        $crossDomain = config('site.crossDomain');
        if (isset($this->_headers['origin']) && preg_match('%'.$crossDomain.'%i', $this->_headers['origin'][0])) {
            //代表本次请求跨域
            $this->isCrossDomain = true;
        }

        //$token = Cookie::get('hst-oa-token');

        //请求参数校验
        $this->paramsValidate();
    }

    /**
     * 输出结果
     *
     * @param  array $result 结果
     * @return response
     */
    protected function response(array $result = array(), array $cookies = array(), array $globalData = array())
    {
        $return = [
            'data' => [
                'localData'  => new \stdClass(),
                'globalData' => new \stdClass(),
            ],
            'cookies' => new \stdClass(),
        ];

        if (!empty($result)) {
            $return['data']['localData'] = $result;
        }
        if (!empty($cookies)) {
            $return['cookies'] = json_decode(json_encode($cookies));
        }

        if (!empty($globalData)) {
            $return['data']['globalData'] = $globalData;
        }

        $responseHeaders = [];
        if ($this->isCrossDomain) {
            $responseHeaders = ['Access-Control-Allow-Credentials' => 'true', 'Access-Control-Allow-Origin' => '*'];
        }

        return response()->json([
            'state' => [
                "code" => 0,// 0=成功 非0=失败
                "msg" => '',//失败理由
            ],
            'data'     => $return['data'],
            'cookies'  => $return['cookies'],
        ], 200, $responseHeaders);
    }

    /**
     * 设置错误信息
     *
     * @param string $errorMsg 错误描述
     * @param int $code 错误状态码
     * @return bool
     */
    protected function error($errorMsg = '', $code = 400)
    {
        if (empty($errorMsg)) {
            if (!isset($this->_errCodes[$code])) {
                $code = '400';
            }
            $errorMsg = $this->_errCodes[$code];
        }
        throw new ApiException($errorMsg, $code);
    }

    /**
     * 根据给定的规则校验请求参数
     *
     * @param  array $args
     * @param  array $rules
     * @param  array $messages
     * @return boolean
     */
    protected function _validate(array $args, array $rules, array $messages = [])
    {
        $vResult = Validator::make($args, $rules, $messages);

        if ($vResult->fails()) {
            return $this->error($vResult->errors()->first());
        }

        return true;
    }

    /**
     * 请求参数检测
     *
     * @return mixed
     */
    abstract protected function paramsValidate();

}
