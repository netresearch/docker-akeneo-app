<?php
namespace Netresearch\AkeneoBootstrap\Console;

use Netresearch\AkeneoBootstrap\Bootstrap;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateKernel;
use Netresearch\AkeneoBootstrap\Bootstrap\BootstrapInterface;
use Netresearch\AkeneoBootstrap\Bootstrap\GenerateConfigs;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this
            ->setName('akeneo-bootstrap')
            ->setDescription('Bootstrap Akeneo before running it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bootstrap = new Bootstrap($output);
        $bootstrap->bootAkeneo();
    }
}