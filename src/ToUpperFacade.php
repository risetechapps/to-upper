<?php

namespace RiseTech\ToUpper;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RiseTech\ToUpper\Skeleton\SkeletonClass
 */
class ToUpperFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'to-upper';
    }
}
