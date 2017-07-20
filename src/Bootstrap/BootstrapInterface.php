<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

interface BootstrapInterface
{
    public function __construct(OutputInterface $output);

    public function init();

    /**
     * @return String
     */
    public function getMessage();

    public function run();

    /**
     * @return KernelInterface
     */
    public function getKernel();
}