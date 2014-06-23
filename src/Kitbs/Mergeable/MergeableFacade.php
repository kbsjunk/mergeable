<?php namespace Kitbs\Mergeable;

use Illuminate\Support\Facades\Facade;

class MergeableFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'mergeable'; }

}
