<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use PragmaRX\Support\Config;
use PragmaRX\Tracker\Eventing\EventStorage;

class Error extends Model
{
    use HasFactory;

    protected $table = 'avt_errors';

    public function getMessageFromThrowable($throwable)
    {
        if ($message = $throwable->getMessage()) {
            return $message;
        }

        return $message;
    }

    public function getCodeFromThrowable($throwable)
    {
        if (method_exists($throwable, 'getCode') && $code = $throwable->getCode()) {
            return $code;
        }

        if (method_exists($throwable, 'getStatusCode') && $code = $throwable->getStatusCode()) {
            return $code;
        }
    }
}
