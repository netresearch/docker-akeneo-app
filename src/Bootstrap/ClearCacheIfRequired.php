<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


class ClearCacheIfRequired extends BootstrapAbstract
{
    public function getMessage()
    {
        return $this->isCacheClearRequired() ? 'Clearing all caches' : null;
    }

    public function run()
    {
        if ($this->isCacheClearRequired()) {
            $this->fs->remove($this->getKernel()->getCacheDir());
        }
    }

}