<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Akeneo\Bundle\StorageUtilsBundle\DependencyInjection\AkeneoStorageUtilsExtension;

class SeizePimInstallation extends BootstrapAbstract
{
    protected $isDbInstalled;

    public function init() {
        /* @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->getKernel()->getContainer()->get('doctrine')->getConnection();
        try {
            $connection->query("SELECT 1 FROM pim_catalog_channel")->fetch();
            $this->isDbInstalled = true;
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->isDbInstalled = false;
        }
    }

    public function getMessage()
    {
        return ($this->isDbInstalled ? 'Seizing' : 'Running') . ' installation';
    }

    public function run()
    {
        $dirs = $this->getKernel()->getContainer()->get('pim_installer.directories_registry')->getDirectories();
        $this->fs->mkdir($dirs);
        ChownDirectories::registerDirectories($dirs);

        $this->runCommand('pim:installer:check-requirements');

        if (!$this->isDbInstalled) {
            $this->runCommand('pim:installer:db');
        } else {
            $this->runCommand('doctrine:schema:update');
            $storageDriver = $this->getKernel()->getContainer()->getParameter('pim_catalog_product_storage_driver');
            if ($storageDriver === AkeneoStorageUtilsExtension::DOCTRINE_MONGODB_ODM) {
                $this->runCommand('doctrine:mongodb:schema:update');
            }
        }

        $this->runCommand('pim:installer:assets');
        ChownDirectories::registerDirectories([
            $this->getKernel()->getRootDir() . '/../web/css',
            $this->getKernel()->getRootDir() . '/../web/js',
        ]);
    }
}