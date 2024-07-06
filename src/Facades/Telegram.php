<?php

namespace Mollsoft\Telegram\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mollsoft\Telegram\Telegram
 */
class Telegram extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mollsoft\Telegram\Telegram::class;
    }
}
