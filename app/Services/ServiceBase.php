<?php

namespace App\Services;

class ServiceBase {

    protected function failureResult(string $message)
    {
        return new ServiceFailureResult($message);
    }

    protected function isFailureResult($result)
    {
        return @get_class($result) == ServiceFailureResult::class;
    }
}