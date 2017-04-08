<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Services\ServiceException;

/**
 * 服务抽象基类
 *
 * 负责处理所有服务需要的公共数据
 *
 * Class Service
 * @package App\Services
 */
Abstract class ServiceAbstract
{
    /**
     * 返回值状态
     *
     * 0=正常返回 >0=异常返回
     *
     * @var int
     */
    protected $returnCode = 0;

    /**
     * 当状态 >0 时使用
     *
     * @var string
     */
    protected $returnMsg = '';

    /**
     * 服务请求参数
     *
     * @var array
     */
    protected $_params = array();

    /**
     * request header 参数
     * @var array
     */
    protected $_headers = array();

    /**
     * Request 对象
     *
     * @var Illuminate\Http\Request
     */
    protected $request = null;

    /**
     * Service constructor.
     *
     * @param array $params 请求参数数组
     */
    public function __construct($params)
    {
        $this->request = request();
        $this->_params = $params;
    }

    /**
     * 构造返回值
     *
     * @param array $data
     * @param array $cookies
     * @return array
     */
    protected function response(array $data = array(), array $cookies = array())
    {
        return [
            'code' => $this->returnCode,
            'msg' => $this->returnMsg,
            'data' => $data,
            'cookies' => $cookies,
        ];
    }

    /**
     * 获取 response error 返回值
     *
     * @return array
     */
    public function getError()
    {
        return [
            'code' => $this->returnCode,
            'msg' => $this->returnMsg,
            'data' => new \stdClass(),
            'cookies' => new \stdClass(),
        ];
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误描述
     * @param int $code 错误状态码
     * @throws ServiceException
     */
    protected function error($error = '', $code = 400)
    {
        $this->returnCode = $code;
        $this->returnMsg  = $error;
        throw new ServiceException();
    }

    /**
     * 根据给定的规则校验请求参数
     *
     * @param  array  $args
     * @param  array  $rules
     * @param  array  $messages
     * @return boolean
     */
    protected function _validate(array $args, array $rules, array $messages = [])
    {
        $vResult = Validator::make($args, $rules, $messages);
        if ($vResult->fails()) {
            $this->error($vResult->errors()->first(), 400);
        }
        return true;
    }

    /**
     * 所有服务必须实现的，服务被调用时自动运行本方法检测你的服务参数
     *
     * @return mixed
     */
    abstract protected function paramsValidate();

    /**
     * 所有服务必须实现的，服务被调用时自动运行本方法
     *
     * @return mixed
     */
    abstract protected function run();
}