<?php

use src\Application;

/**
 * Get the available container instance.
 *
 * @return mixed|Application
 */
function app($abstract = null, array $parameters = [])
{
    if (is_null($abstract)) {
        return Application::getInstance();
    }

    return Application::getInstance()->make($abstract, $parameters);
}
