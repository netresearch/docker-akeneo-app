<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Symfony\Component\Process\Process;

class LinkStaticDirectories extends BootstrapAbstract
{
    protected $links = [];

    public function init()
    {
        $kernel = $this->getKernel();
        $staticLogDir = $kernel->getRootDir() . '/logs';
        $this->links[$staticLogDir] = ($kernel->getLogDir() === $staticLogDir) ? null : $kernel->getLogDir();
    }

    public function getMessage()
    {
        return '';
    }

    public function run()
    {
        foreach ($this->links as $link => $target) {
            if ($target) {
                $this->output->write("Linking $link to $target");
                if (!is_link($link)) {
                    $this->fs->remove($link);
                }
                $process = new Process(sprintf('ln -sf %s %s', escapeshellarg($target), escapeshellarg($link)));
                $process->run();
                $this->output->writeln($process->getExitCode() > 0 ? ' FAILURE' : '');
            } else {
                if (is_link($link) || is_file($link)) {
                    $this->fs->remove($link);
                }
                $this->fs->mkdir($link);
            }
        }
    }

}