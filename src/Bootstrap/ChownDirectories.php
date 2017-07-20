<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Symfony\Component\Process\Process;

class ChownDirectories extends BootstrapAbstract
{
    protected $chown;

    protected static $directories = [];

    public static function registerDirectories(array $directories)
    {
        self::$directories = array_unique(array_merge(self::$directories, $directories));
    }

    public function init()
    {
        $this->chown = getenv('WEB_USER');
        if (!$this->chown) {
            if ($apacheEnvFile = getenv('APACHE_ENVVARS')) {
                $process = new Process(
                    'bash -c \'source ' . escapeshellarg($apacheEnvFile)
                    . ' && echo "$APACHE_RUN_USER.$APACHE_RUN_GROUP"\'');
                $process->run();
                $this->chown = trim($process->getOutput());
            }
        }
    }

    public function getMessage()
    {
        return $this->chown ? "Seizing chown ({$this->chown})" : null;
    }

    public function run()
    {
        foreach (self::$directories as $directory) {
            $process = new Process("chown -R {$this->chown} " . escapeshellarg($directory));
            $process->run();
        }
    }

}