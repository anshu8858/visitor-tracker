<?php

namespace Anshu8858\VisitorTracker\Http\Ctrlr;

class Error extends CtrlrMgr
{
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
