<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Pim\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

abstract class BootstrapAbstract implements BootstrapInterface
{
    /**
     * @var KernelInterface
     */
    private static $kernel;

    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var Filesystem
     */
    protected $fs;

    private static $cacheClearRequired = false;

    private static $commandExecutor;

    public final function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->fs = new Filesystem();
    }

    public function init()
    {
    }

    protected function setKernel(KernelInterface $kernel)
    {
        self::$kernel = $kernel;
        if (self::$commandExecutor) {
            self::$commandExecutor = null;
        }
    }

    /**
     * @return KernelInterface
     */
    public function getKernel()
    {
        if (!self::$kernel) {
            throw new \RuntimeException('No kernel registered');
        }
        return self::$kernel;
    }

    protected function isCacheClearRequired($flag = null)
    {
        if ($flag !== null) {
            self::$cacheClearRequired = $flag;
        }
        return self::$cacheClearRequired;
    }

    protected function runCommand($command, array $params = [])
    {
        if (!self::$commandExecutor) {
            $input = new ArrayInput([]);
            $application = new Application($this->getKernel());
            self::$commandExecutor = new CommandExecutor($input, $this->output, $application);
        }
        return self::$commandExecutor->runCommand($command, $params);
    }
}