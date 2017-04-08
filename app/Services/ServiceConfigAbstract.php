<?php

namespace App\Services;

use Illuminate\Support\Arr;

/**
 * 服务配置文件操作抽象基类
 *
 * 负责处理所有服务的配置文件的读取和设置
 *
 * Class ServiceConfigAbstract
 * @package App\Services
 */
Abstract class ServiceConfigAbstract
{
    protected static $_config = array();

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($key)
    {
        return Arr::has(static::$_config, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return static::$_config;
        }

        return Arr::get(static::$_config, $key, $default);
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public static function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set(static::$_config, $key, $value);
        }
    }
}