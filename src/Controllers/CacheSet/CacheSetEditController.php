<?php

namespace src\Controllers\CacheSet;

use src\Controllers\BaseController;

class CacheSetEditController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function isCallableFromRouter($actionName)
    {
        // all public methods can be called by router
        return true;
    }

    public function index()
    {
    }
}
