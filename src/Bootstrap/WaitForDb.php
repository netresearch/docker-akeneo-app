<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


class WaitForDb extends BootstrapAbstract
{
    const WARN_EVERY = 10;

    public function getMessage()
    {
        return 'Waiting for DB';
    }

    public function run()
    {
        /* @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->getKernel()->getContainer()->get('doctrine')->getConnection();
        $tries = 0;
        while (!$connection->isConnected()) {
            try {
                $connection->connect();
            } catch (\PDOException $e) {
                $tries++;
                if ($tries % self::WARN_EVERY === 0) {
                    $this->output->writeln("  <comment>Could not connect to DB after $tries tries - is it up?</comment>");
                    $this->output->writeln("  <comment>Error message:</comment> {$e->getMessage()})");
                }
                sleep(1);
            }
        }
    }

}