<?php
namespace App\Utility\Pool\Redis;

use Co\Redis;
use EasySwoole\Component\Pool\PoolObjectInterface;


class RedisObject extends Redis implements PoolObjectInterface
{
    function gc()
    {
        // TODO: Implement gc() method.
        $this->close();
    }
    function objectRestore()
    {
        // TODO: Implement objectRestore() method.
    }
    function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}