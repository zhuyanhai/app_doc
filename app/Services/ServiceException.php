<?php

namespace App\Services;

class ServiceException extends \Exception
{
    /**
     * ServiceException constructor.
     *
     * @param string $response
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($response = "", $code = 0, Exception $previous = null)
    {
        //empty
    }
}