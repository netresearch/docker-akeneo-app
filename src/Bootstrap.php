<?php


namespace Netresearch\AkeneoBootstrap;


use Netresearch\AkeneoBootstrap\Bootstrap\BootKernel;
use Netresearch\AkeneoBootstrap\Bootstrap\EnsureChownDirectories;
use Netresearch\AkeneoBootstrap\Bootstrap\ClearCacheIfRequired;
use Netresearch\AkeneoBootstrap\Bootstrap\FixRequirements;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateKernel;
use Netresearch\AkeneoBootstrap\Bootstrap\BootstrapInterface;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateConfigs;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateParameters;
use Netresearch\AkeneoBootstrap\Bootstrap\LinkStaticDirectories;
use Netresearch\AkeneoBootstrap\Bootstrap\EnsurePimInstallation;
use Netresearch\AkeneoBootstrap\Bootstrap\SetExportImportPaths;
use Netresearch\AkeneoBootstrap\Bootstrap\WaitForDb;
use Netresearch\AkeneoBootstrap\Util\Composer;
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

    protected function runFromPackages($type)
    {
        $bootstraps = [];
        foreach (Composer::getAkeneoBootstrapPackageExtras() as $packageName => $packageExtra) {
            if (array_key_exists($type, $packageExtra)) {
                $classes = $packageExtra[$type];
                $path = 'extra.' . Composer::EXTRA_KEY . '.' . $type;
                if (!is_array($classes)) {
                    throw new \RuntimeException("{$path} must be array in {$packageName}/composer.json");
                }
                foreach ($classes as $i => $class) {
                    if (!is_string($class) || !class_exists($class)) {
                        throw new \RuntimeException("{$path}.{$i} must be valid class name in {$packageName}/composer.json");
                    }
                    $interface = 'Netresearch\\AkeneoBootstrap\\Bootstrap\\BootstrapInterface';
                    if (!in_array($interface, class_implements($class))) {
                        throw new \RuntimeException("$class must implement $interface");
                    }
                    $bootstraps[] = new $class($this->output);
                }
            }
        }
        $this->run($bootstraps);
    }

    public function generateConfiguration($clearCacheIfRequired = true)
    {
        $this->configurationGenerated = true;
        $this->run([
            new GenerateKernel($this->output),
            new GenerateConfigs($this->output),
            new GenerateParameters($this->output),
            new FixRequirements($this->output)
        ]);
        $this->runFromPackages('generate');
        if ($clearCacheIfRequired) {
            $this->run([
                new ClearCacheIfRequired($this->output)
            ]);
        }
    }

    public function bootAkeneo()
    {
        if (!$this->configurationGenerated) {
            $this->generateConfiguration();
        }

        $runCommands = [
            new BootKernel($this->output),
            new WaitForDb($this->output),
            new EnsurePimInstallation($this->output),
            new SetExportImportPaths($this->output),
            new LinkStaticDirectories($this->output),
            new EnsureChownDirectories($this->output)
        ];

        if (getenv('USE_FIXTURE_PATHS')) {
            // removed SetExportImportPaths command
            unset($runCommands[3]);
        }

        $this->run($runCommands);
        $this->runFromPackages('boot');
    }
}
