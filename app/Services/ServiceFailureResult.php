<?php

namespace App\Services;

class ServiceFailureResult {

    public $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

}