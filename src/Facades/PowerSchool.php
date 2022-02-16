<?php

namespace TONYLABS\PowerSchool\Api\Facades;

use Illuminate\Support\Facades\Facade;
use TONYLABS\PowerSchool\Api\Request;
use TONYLABS\PowerSchool\Api\Response;
use TONYLABS\PowerSchool\Api\RequestBuilder;

class PowerSchool extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return RequestBuilder::class;
    }
}
