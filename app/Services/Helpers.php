<?php

use App\Services;
use App\Services\ServiceException;

if (! function_exists('callService')) {

    /**
     * 在服务中调用服务
     *
     * @param string $service 服务名称 例如：foundation.getVerifyCodeV1
     * @param array $params 服务需要的参数
     * @return array
     */
    function callService($service, $params = array())
    {
        $services   = explode('.', $service);
        $className  = 'App\Services\\'.ucfirst($services[0]).'\\Controllers\\'.ucfirst($services[1]);

        $serviceObj = new $className($params);
        try {
            if ($serviceObj->paramsValidate()) {//校验请求参数
                return $serviceObj->run();
            }
        } catch(ServiceException $se) {
            //empty
        }

        return $serviceObj->getError();
    }
}

if (! function_exists('objectToArray')) {

    /**
     * 对象转换成数组
     */
    function objectToArray($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }
}
