<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Symfony\Component\Process\Process;

class EnsureChownDirectories extends BootstrapAbstract
{
    protected $chown;

    protected static $directories = [];

    public static function registerDirectories(array $directories)
    {
        self::$directories = array_unique(array_merge(self::$directories, $directories));
    }

    public function init()
    {
        $this->chown = getenv('WEB_USER') ?: 'www-data.www-data';
    }

    public function getMessage()
    {
        return $this->chown ? "Ensuring chown ({$this->chown})" : null;
    }

    public function run()
    {
        foreach (self::$directories as $directory) {
            $process = new Process("chown -R {$this->chown} " . escapeshellarg($directory));
            $process->run();
        }
    }

}