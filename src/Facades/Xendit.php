<?php

namespace Laraditz\Xendit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laraditz\Xendit\Skeleton\SkeletonClass
 */
class Xendit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'xendit';
    }
}
