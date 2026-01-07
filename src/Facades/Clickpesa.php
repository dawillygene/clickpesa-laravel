<?php

namespace Dawilly\Dawilly\Facades;

use Illuminate\Support\Facades\Facade;

class Clickpesa extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'clickpesa';
    }
}
