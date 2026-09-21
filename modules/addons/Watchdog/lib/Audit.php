<?php

namespace WHMCS\Module\Addon\Watchdog;

use WHMCS\Database\Capsule;

class Audit
{
    private $env;

    public function __construct()
    {
        $this->env = $this->setEnv();
    }

    private function setEnv()
    {
        $configFile = ROOTDIR . DIRECTORY_SEPARATOR . 'configuration.php';
        if (!is_file($configFile)) {
            throw new \RuntimeException('WHMCS configuration.php not found');
        }

        $output = new \stdClass();
        $customadminpath = null;
        $downloads_dir = null;
        $crons_dir = null;

        include $configFile;

        $output->adminPath = isset($customadminpath) && $customadminpath ? $customadminpath : 'admin';
        $output->downloadsPath = isset($downloads_dir) && $downloads_dir ? $downloads_dir : 'downloads';
        $output->cronsDir = isset($crons_dir) && $crons_dir ? $crons_dir : 'crons';
        $output->rootDir = ROOTDIR;

        return $output;
    }

    private function scanDirRecursive($dir, array &$output = [])
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            return $output;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return $output;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_link($path)) {
                continue;
            }

            if (is_dir($path)) {
                $this->scanDirRecursive($path, $output);
                continue;
            }

            if (!is_file($path) || strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }

            $hash = @md5_file($path);
            if ($hash === false) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr(realpath($path), strlen(rtrim($this->env->rootDir, DIRECTORY_SEPARATOR)))), '/');
            if ($relative !== '') {
                $output[$relative] = $hash;
            }
        }

        return $output;
    }

    public function run(array $data)
    {
        $checksum = $data['checksum'] ?? null;

        if (!is_array($checksum) || !$checksum) {
            throw new \InvalidArgumentException('Invalid checksum manifest');
        }

        $fileSystem = $this->scanDirRecursive($this->env->rootDir);
        $output = [];

        foreach ($fileSystem as $path => $detected) {
            if (!array_key_exists($path, $checksum)) {
                $status = 2;
                $expected = null;
            } elseif (!hash_equals((string) $checksum[$path], (string) $detected)) {
                $status = 3;
                $expected = (string) $checksum[$path];
            } else {
                continue;
            }

            if ($this->isWhitelisted($path)) {
                continue;
            }

            $output[] = [
                'path' => substr($path, 0, 260),
                'detected' => $detected,
                'expected' => $expected,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        foreach (array_diff_key($checksum, $fileSystem) as $path => $expected) {
            if ($this->isWhitelisted((string) $path)) {
                continue;
            }

            $output[] = [
                'path' => substr((string) $path, 0, 260),
                'detected' => null,
                'expected' => (string) $expected,
                'status' => 4,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        Capsule::table('wd_audit')->truncate();

        if ($output) {
            foreach (array_chunk($output, 100) as $chunk) {
                Capsule::table('wd_audit')->insert($chunk);
            }
        }

        Capsule::table('tbladdonmodules')
            ->where('module', 'Watchdog')
            ->where('setting', 'lastRun')
            ->update(['value' => date('Y-m-d H:i:s')]);

        return $output;
    }

    private function isWhitelisted($path)
    {
        return Capsule::table('wd_whitelist')
            ->where('path', $path)
            ->exists();
    }
}
