<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


class BootKernel extends BootstrapAbstract
{
    public function getMessage()
    {
        return 'Booting kernel';
    }

    public function run()
    {
        $this->getKernel()->boot();
    }

}