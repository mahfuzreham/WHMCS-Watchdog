<?php

use WHMCS\Module\Addon\Watchdog\Audit;
use WHMCS\Module\Addon\Watchdog\Settings;

add_hook('AfterCronJob', 10, function () {
    try {
        $settings = (new Settings())->listing();
        $frequency = max(1, (int) ($settings['checkFrequency'] ?? 24));
        $lastRun = trim((string) ($settings['lastRun'] ?? ''));

        if ($lastRun !== '') {
            $last = new DateTimeImmutable($lastRun);
            $now = new DateTimeImmutable('now');
            $hours = ($now->getTimestamp() - $last->getTimestamp()) / 3600;

            if ($hours < $frequency) {
                return;
            }
        }

        $version = (string) ($settings['WHMCSVersion'] ?? '');
        if ($version === '') {
            logActivity('Watchdog: WHMCS version could not be determined.');
            return;
        }

        $url = 'https://raw.githubusercontent.com/mahfuzreham/WHMCS-Watchdog/main/checksum/' .
            rawurlencode($version) . '.json';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'WHMCS-Watchdog/2.2',
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false || $httpCode !== 200) {
            logActivity('Watchdog: checksum manifest unavailable for ' . $version .
                ($curlError ? ' (' . $curlError . ')' : ''));
            return;
        }

        $checksum = json_decode($body, true);
        if (!is_array($checksum) || !$checksum) {
            logActivity('Watchdog: invalid checksum manifest for ' . $version . '.');
            return;
        }

        $audit = new Audit();
        $result = $audit->run([
            'checksum' => $checksum,
            'version' => $version,
        ]);

        $statusCount = [];
        foreach ($result as $row) {
            $status = (int) ($row['status'] ?? 0);
            $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
        }

        logActivity('Watchdog: integrity check completed for ' . $version .
            '. Findings: ' . json_encode($statusCount));
    } catch (\Throwable $e) {
        logActivity('Watchdog: cron check failed: ' . $e->getMessage());
    }
});
