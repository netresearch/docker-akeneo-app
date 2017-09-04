<?php


namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Akeneo\Component\StorageUtils\Saver\BulkSaverInterface;

class SetExportImportPaths extends BootstrapAbstract
{
    public function getMessage()
    {
        return 'Setting import/export paths';
    }

    public function run()
    {
        $paths = [];

        /* @var \Akeneo\Bundle\BatchBundle\Job\JobInstanceRepository $jobRepository */
        $jobRepository = $this->getKernel()->getContainer()->get('akeneo_batch.job.job_instance_repository');
        /* @var \Akeneo\Bundle\StorageUtilsBundle\Doctrine\Common\Saver\BaseSaver $saver */
        $saver = $this->getKernel()->getContainer()->get('akeneo_batch.saver.job_instance');
        $bulkAvailable = $saver instanceof BulkSaverInterface;

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
                if (!$bulkAvailable) {
                    $saver->save($job);
                }
            }
            if ($bulkAvailable) {
                $saver->saveAll($jobs);
            }
        }
        $this->fs->mkdir($paths);
        EnsureChownDirectories::registerDirectories($paths);
    }

}