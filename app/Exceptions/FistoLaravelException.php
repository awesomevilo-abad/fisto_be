<?php

namespace App\Exceptions;

use Exception;

class FistoLaravelException extends Exception
{
  protected $_data;

  public function __construct($message="", $code=0 , Exception $previous=NULL, $data = NULL)
    {
      $this->_data = $data;
      parent::__construct($message, $code, $previous);
    }

  public function getData()
    {
      return $this->_data;
    }

  public function render($request)
    {
      return response([
        "code" => $this->getCode(),
        "message" => $this->getMessage(),
        "errors" => $this->getData()
      ], $this->getCode());
    }
}
