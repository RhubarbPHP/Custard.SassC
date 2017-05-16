<?php

namespace Rhubarb\Custard\SassC;

use Rhubarb\Crown\Application;

class CompileScssApplication extends Application
{
    protected function getModules()
    {
        return [new CompileScssModule()];
    }
}
