<?php

namespace Incenteev\ParameterHandler;

use Netresearch\AkeneoBootstrap\Bootstrap;
use Symfony\Component\Console\Output\ConsoleOutput;

class ScriptHandler
{
    public static function buildParameters()
    {
        $bootstrap = new Bootstrap(new ConsoleOutput());
        $bootstrap->generateConfiguration(false);
    }
}
