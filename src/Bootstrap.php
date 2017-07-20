<?php


namespace Netresearch\AkeneoBootstrap;


use Netresearch\AkeneoBootstrap\Bootstrap\BootKernel;
use Netresearch\AkeneoBootstrap\Bootstrap\ChownDirectories;
use Netresearch\AkeneoBootstrap\Bootstrap\ClearCacheIfRequired;
use Netresearch\AkeneoBootstrap\Bootstrap\FixRequirements;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateKernel;
use Netresearch\AkeneoBootstrap\Bootstrap\BootstrapInterface;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateConfigs;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateParameters;
use Netresearch\AkeneoBootstrap\Bootstrap\SeizePimInstallation;
use Netresearch\AkeneoBootstrap\Bootstrap\SetExportImportPaths;
use Netresearch\AkeneoBootstrap\Bootstrap\WaitForDb;
use Symfony\Component\Console\Output\OutputInterface;

class Bootstrap
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected $configurationGenerated = false;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param BootstrapInterface[] $bootstraps
     */
    protected function run($bootstraps)
    {
        foreach ($bootstraps as $bootstrap) {
            $bootstrap->init();
            $msg = $bootstrap->getMessage();
            if ($msg) {
                $this->output->writeln($msg);
            }
            $bootstrap->run();
        }
    }

    public function generateConfiguration()
    {
        $this->configurationGenerated = true;
        $this->run([
            new GenerateKernel($this->output),
            new GenerateConfigs($this->output),
            new GenerateParameters($this->output),
            new FixRequirements($this->output)
        ]);
    }

    public function bootAkeneo()
    {
        if (!$this->configurationGenerated) {
            $this->generateConfiguration();
        }
        $this->run([
            new ClearCacheIfRequired($this->output),
            new BootKernel($this->output),
            new WaitForDb($this->output),
            new SeizePimInstallation($this->output),
            new SetExportImportPaths($this->output),
            new ChownDirectories($this->output)
        ]);
    }
}