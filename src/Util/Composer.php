<?php


namespace Netresearch\AkeneoBootstrap\Util;


class Composer
{
    const EXTRA_KEY = 'netresearch/akeneo-bootstrap';

    protected static $installedPackages;

    public static function getVendorDir() {
        return realpath('./vendor');
    }

    public static function getInstalledPackages() {
        if (!self::$installedPackages) {
            $file = self::getVendorDir() . '/composer/installed.json';
            $json = @file_get_contents($file);
            if (!$json) {
                throw new \RuntimeException("Could not load $file");
            }
            self::$installedPackages = @json_decode($json, true);
            if (!self::$installedPackages) {
                throw new \RuntimeException("Could not decode $file");
            }
        }
        return self::$installedPackages;
    }

    public static function getAkeneoBootstrapPackageExtras()
    {
        $extras = [];
        foreach (self::getInstalledPackages() as $package) {
            if (isset($package['extra']) && isset($package['extra'][self::EXTRA_KEY])) {
                $extras[$package['name']] = $package['extra'][self::EXTRA_KEY];
            }
        }
        return $extras;
    }
}