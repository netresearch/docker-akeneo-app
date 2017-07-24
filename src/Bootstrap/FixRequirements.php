<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Netresearch\AkeneoBootstrap\Util\Composer;

class FixRequirements extends BootstrapAbstract
{
    public function getMessage()
    {
        return 'Fixing ORO requirements';
    }

    public function run()
    {
        $kernel = $this->getKernel();
        $cacheDir = dirname($kernel->getCacheDir());
        $logDir = $kernel->getLogDir();

        $this->replace(
            'OroRequirements.php',
            [
                "'web/bundles'" => '"$baseDir/web/bundles"',
                '$baseDir.\'/\'.$directory' => '$directory',
                "'app/cache'" => "'{$cacheDir}'",
                "'app/logs'" => "'{$logDir}'"
            ]
        );
        $this->replace(
            'SymfonyRequirements.php',
            [
                "__DIR__.'/../var/cache'" => "'{$cacheDir}'",
                "__DIR__.'/../var/logs'" => "'{$logDir}'",
                'app/cache/ or var/cache/' => $cacheDir,
                'app/logs/ or var/logs/' => $logDir
            ]
        );
    }

    protected function replace($file, $replacements)
    {

        $contents = file_get_contents(Composer::getVendorDir() . '/akeneo/pim-community-dev/app/' . $file);
        foreach ($replacements as $search => $replacement) {
            $contents = str_replace($search, $replacement, $contents);
        }
        $this->fs->dumpFile($this->getKernel()->getRootDir() . '/' . $file, $contents);
    }

}