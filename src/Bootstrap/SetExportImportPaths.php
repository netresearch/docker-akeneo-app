<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;

class SetExportImportPaths extends BootstrapAbstract
{
    public function getMessage()
    {
        return 'Setting import/export paths';
    }

    public function run()
    {
        $paths = [];

        $container = $this->getKernel()->getContainer();
        /* @var \Doctrine\ORM\EntityManager $jobManager */
        $jobManager = $container->get('akeneo_batch.job_repository')->getJobManager();
        $jobRepository = $jobManager->getRepository('Akeneo\Component\Batch\Model\JobInstance');

        foreach (['import', 'export'] as $type) {
            $path = rtrim(getenv(strtoupper($type) . '_PATH') ?: "/var/opt/akeneo/{$type}s", '/');
            $paths[] = $path;
            $jobs = $jobRepository->findBy(['type' => $type]);
            foreach ($jobs as $job) {
                /* @var \Akeneo\Component\Batch\Model\JobInstance $job */
                $jobPath = $path;
                if ($overrideJobPath = getenv(strtoupper($job->getCode()) . '_PATH')) {
                    $jobPath = rtrim($overrideJobPath, '/');
                    $paths[] = $jobPath;
                }
                $parameters = $job->getRawParameters();
                if (array_key_exists('filePath', $parameters) && $parameters['filePath']) {
                    $parameters['filePath'] = $jobPath . '/' . basename($parameters['filePath']);
                }
                $job->setRawParameters($parameters);
                $jobManager->persist($job);
            }
        }

        $jobManager->flush();

        $this->fs->mkdir($paths);
        EnsureChownDirectories::registerDirectories($paths);
    }

}