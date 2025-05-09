<?php
namespace dmytrof\DuiBucketSDK\Laravel;

use Illuminate\Support\Facades\Facade;

class DuiBucket extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dui-bucket-sdk';
    }
}
