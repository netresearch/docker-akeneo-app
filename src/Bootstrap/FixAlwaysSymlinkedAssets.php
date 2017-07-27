<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Netresearch\AkeneoBootstrap\Util\Composer;

class FixAlwaysSymlinkedAssets extends BootstrapAbstract
{
    public function getMessage()
    {
        return 'Fixing assets always being symlinked';
    }

    public function run()
    {
        $file = Composer::getVendorDir() . '/akeneo/pim-community-dev/src/Pim/Bundle/InstallerBundle/Command/AssetsCommand.php';
        $this->fs->dumpFile(
            $file,
            str_replace(
                'null !== $input->getOption(\'symlink\')',
                '$input->getOption(\'symlink\')',
                file_get_contents($file)
            )
        );
    }

}