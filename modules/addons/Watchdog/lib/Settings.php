<?php

namespace WHMCS\Module\Addon\Watchdog;

use WHMCS\Database\Capsule;

class Settings
{
    private const ALLOWED_SETTINGS = [
        'checkFrequency',
        'actionsTaken',
        'recipients',
    ];

    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setting'])) {
            $this->save();
        }
    }

    public function listing()
    {
        $output = [
            'actionsTaken' => [],
            'recipients' => '',
            'checkFrequency' => '24',
            'lastRun' => '',
        ];

        $rows = Capsule::table('tbladdonmodules')
            ->where('module', 'Watchdog')
            ->get(['setting', 'value']);

        foreach ($rows as $row) {
            if ($row->setting === 'actionsTaken') {
                $decoded = json_decode((string) $row->value, true);
                foreach (is_array($decoded) ? $decoded : [] as $key) {
                    $output['actionsTaken'][(string) $key] = true;
                }
            } elseif ($row->setting === 'recipients') {
                $decoded = json_decode((string) $row->value, true);
                $output['recipients'] = implode(PHP_EOL, is_array($decoded) ? $decoded : []);
            } else {
                $output[$row->setting] = (string) $row->value;
            }
        }

        $versionRow = Capsule::table('tblconfiguration')
            ->where('setting', 'Version')
            ->first(['value']);

        $output['WHMCSVersion'] = $versionRow ? (string) $versionRow->value : '';

        return $output;
    }

    private function requireAdminToken()
    {
        if (!function_exists('check_token') || !check_token('WHMCS.admin.default')) {
            http_response_code(403);
            exit('Invalid security token');
        }
    }

    private function save()
    {
        $this->requireAdminToken();

        $setting = isset($_POST['setting']) ? (string) $_POST['setting'] : '';
        if (!in_array($setting, self::ALLOWED_SETTINGS, true)) {
            http_response_code(400);
            exit('Invalid setting');
        }

        $value = $_POST['value'] ?? '';

        if ($setting === 'checkFrequency') {
            $frequency = filter_var($value, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 2160],
            ]);

            if ($frequency === false) {
                http_response_code(422);
                exit('Invalid check frequency');
            }

            $value = (string) $frequency;
        } elseif ($setting === 'actionsTaken') {
            $values = is_array($value) ? $value : [$value];
            $allowed = ['neutralize', 'notify'];
            $values = array_values(array_intersect($allowed, array_map('strval', $values)));
            $value = json_encode(array_unique($values), JSON_THROW_ON_ERROR);
        } elseif ($setting === 'recipients') {
            $lines = preg_split('/\\R+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $emails = [];

            foreach ($lines as $email) {
                $email = trim($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(422);
                    exit('Invalid email address');
                }
                $emails[] = $email;
            }

            $value = json_encode(array_values(array_unique($emails)), JSON_THROW_ON_ERROR);
        }

        Capsule::table('tbladdonmodules')
            ->where('module', 'Watchdog')
            ->where('setting', $setting)
            ->update(['value' => $value]);

        $heading = isset($_POST['heading']) ? (int) $_POST['heading'] : 1;
        header('Location: addonmodules.php?module=Watchdog&view=Settings&heading=' . $heading);
        exit;
    }
}
