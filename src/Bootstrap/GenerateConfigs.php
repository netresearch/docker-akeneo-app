<?php

namespace Netresearch\AkeneoBootstrap\Bootstrap;


use Netresearch\AkeneoBootstrap\Util\Composer;

class GenerateConfigs extends BootstrapAbstract
{
    protected $environments = [
        'all' => 'local.yml',
        'behat' => 'behat_local.yml',
        'dev' => 'dev_local.yml',
        'prod' => 'prod_local.yml',
        'test' => 'test_local.yml'
    ];

    public function getMessage()
    {
        return "Generating configs";
    }

    public function run()
    {
        $rootPath = $this->getKernel()->getRootDir();
        $files = $this->getFiles();
        foreach ($this->environments as $environment => $postfix) {
            foreach (['config', 'routing'] as $type) {
                $file = $type . '_' . $postfix;
                $path = $rootPath . '/config/' . $file;
                if (file_exists($path)) {
                    if (!array_key_exists($file, $files)) {
                        $this->fs->remove($path);
                        $this->isCacheClearRequired(true);
                        continue;
                    }
                    if (file_get_contents($path) === $files[$file]) {
                        continue;
                    }
                }
                if (array_key_exists($file, $files)) {
                    $this->fs->dumpFile($path, $files[$file]);
                    $this->isCacheClearRequired(true);
                }
            }
        }
    }

    protected function getFiles()
    {
        $config = $this->getConfigResources('config');
        $routing = $this->getConfigResources('routing');
        $hasCommonRouting = !empty($routing['all']);
        $files = [];
        foreach ($this->environments as $environment => $postfix) {
            $configFile = $routingFile = ['# Autogenerated by ' . __CLASS__];
            $includes = isset($config[$environment]) ? $config[$environment] : [];
            if ($environment !== 'all' && !empty($config['all'])) {
                array_unshift($includes, 'config_' . $this->environments['all']);
            }
            $hasEnvRouting = !empty($routing[$environment]);
            if (!$includes && !$hasEnvRouting && !$hasCommonRouting) {
                continue;
            }
            if ($includes) {
                $configFile[] = 'imports:';
                foreach ($includes as $include) {
                    $configFile[] = "  - { resource: '$include' }";
                }
            }
            $hasEnvDefaultRouting = $environment !== 'all' && file_exists($this->getKernel()->getRootDir() . "/config/routing_{$environment}.yml");
            if ($hasEnvRouting || $hasCommonRouting && $hasEnvDefaultRouting) {
                $configFile[] = 'framework:';
                $configFile[] = '  router:';
                $configFile[] = "    resource: '%kernel.root_dir%/config/routing_$postfix'";
                if ($hasEnvRouting) {
                    foreach ((array)$routing[$environment] as $key => $route) {
                        $routingFile[] = $key . ':';
                        $routingFile[] = '  resource: "' . $route['resource'] . '"';
                        if ($route['prefix']) {
                            $routingFile[] = '  prefix: ' . $route['prefix'];
                        }
                    }
                    $routingFile[] = '';
                }
                if ($environment !== 'all') {
                    if ($hasCommonRouting) {
                        $routingFile[] = '_local:';
                        $routingFile[] = '  resource: routing_' . $this->environments['all'];
                    }
                    if ($hasEnvDefaultRouting) {
                        $routingFile[] = "_{$environment}:";
                        $routingFile[] = "  resource: routing_{$environment}.yml";
                    }
                } else {
                    $routingFile[] = "_main:";
                    $routingFile[] = "  resource: routing.yml";
                }
                $files['routing_' . $postfix] = implode("\n", $routingFile);
            }
            $files['config_' . $postfix] = implode("\n", $configFile);
        }
        return $files;
    }

    protected function getConfigResources($type)
    {
        $resources = [];
        $prefixes = [];
        foreach (Composer::getAkeneoBootstrapPackageExtras() as $package => $extra) {
            if ($extra[$type]) {
                foreach ($extra[$type] as $key => $config) {
                    $msg = "<error>Could not add $type resource $key for $package:</error>\n";
                    if (!is_array($config)) {
                        $this->output->writeln("$msg - wrong format");
                        continue;
                    }
                    if (!isset($config['resource'])) {
                        $this->output->writeln("$msg - missing resource");
                        continue;
                    }
                    $resource = $config['resource'];
                    $envs = isset($config['env']) ? $config['env'] : ['all'];
                    if (is_string($envs)) {
                        $envs = explode(',', $envs);
                    }
                    if (in_array('all', $envs)) {
                        $envs = ['all'];
                    }
                    foreach ($envs as $env) {
                        if (!array_key_exists($env, $resources)) {
                            $resources[$env] = [];
                            $prefixes[$env] = [];
                        }
                        if ($type === 'routing') {
                            $prefix = isset($config['prefix']) ? $config['prefix'] : null;
                            if (array_key_exists($key, $resources[$env]) && (
                                    $resources[$env][$key] !== $resource || $prefixes[$env][$key] !== $prefix
                                )
                            ) {
                                $this->output->writeln("$msg - conflicting resource or prefix for existing routing key $key");
                                continue 2;
                            }
                            $resources[$env][$key] = $resource;
                            $prefixes[$env][$key] = $prefix;
                            if ($env === 'all') {
                                foreach ($resources as $e => $r) {
                                    if ($e !== 'all' && isset($r[$key]) && $r[$key] === $resource && $prefixes[$e][$key] === $prefix) {
                                        unset($resources[$e][$key]);
                                        unset($prefixes[$e][$key]);
                                    }
                                }
                            }
                        } else {
                            if ($env === 'all') {
                                foreach ($resources as $e => $r) {
                                    $resources[$e] = array_diff($r, [$resource]);
                                }
                            }
                            if (!in_array($resource, $resources[$env])) {
                                $resources[$env][] = $resource;
                            }
                        }
                    }
                }
            }
        }
        if ($type === 'routing') {
            $result = [];
            foreach ($resources as $env => $envResources) {
                $result[$env] = [];
                foreach ($envResources as $key => $resource) {
                    $result[$env][$key] = [
                        'resource' => $resource,
                        'prefix' => $prefixes[$env][$key]
                    ];
                }
            }
            return $result;
        }
        return $resources;
    }
}