<?php

namespace Dawilly\Dawilly\Facades;

use Illuminate\Support\Facades\Facade;

class Disbursement extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'disbursement';
    }
}
