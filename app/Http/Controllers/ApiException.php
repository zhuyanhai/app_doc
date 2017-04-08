<?php

namespace App\Http\Controllers;

class ApiException extends \Exception
{
    /**
     * ApiException constructor.
     *
     * @param string $response
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($response = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($response, $code, $previous);
    }
}