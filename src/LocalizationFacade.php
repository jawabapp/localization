<?php

namespace Jawabapp\Localization;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jawabapp\Localization\Skeleton\SkeletonClass
 */
class LocalizationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'localization';
    }
}
