<?php
/**
 * WHMCS Watchdog
 * Secure file-integrity monitoring addon.
 */
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\Watchdog\Dashboard;
use WHMCS\Module\Addon\Watchdog\Whitelist;
use WHMCS\Module\Addon\Watchdog\Settings;

function Watchdog_config()
{
    return [
        'name' => 'Watchdog',
        'description' => 'Detect modified, missing and unknown WHMCS PHP files.',
        'version' => '2.2.0',
        'author' => 'Mahfuz Reham',
        'fields' => [],
    ];
}

function Watchdog_activate()
{
    try {
        if (!Capsule::schema()->hasTable('wd_audit')) {
            Capsule::schema()->create('wd_audit', function ($table) {
                $table->string('path', 260);
                $table->char('detected', 64)->nullable();
                $table->char('expected', 64)->nullable();
                $table->tinyInteger('status');
                $table->timestamp('created_at')->nullable();
                $table->primary('path');
                $table->index('status');
            });
        }

        if (!Capsule::schema()->hasTable('wd_whitelist')) {
            Capsule::schema()->create('wd_whitelist', function ($table) {
                $table->string('path', 260);
                $table->text('notes')->nullable();
                $table->primary('path');
            });
        }

        $defaults = [
            'checkFrequency' => '24',
            'actionsTaken' => json_encode([]),
            'recipients' => json_encode([]),
            'lastRun' => '',
        ];

        foreach ($defaults as $setting => $value) {
            $exists = Capsule::table('tbladdonmodules')
                ->where('module', 'Watchdog')
                ->where('setting', $setting)
                ->exists();

            if (!$exists) {
                Capsule::table('tbladdonmodules')->insert([
                    'module' => 'Watchdog',
                    'setting' => $setting,
                    'value' => $value,
                ]);
            }
        }

        return [
            'status' => 'success',
            'description' => 'Watchdog activated successfully.',
        ];
    } catch (\Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Unable to activate Watchdog: ' . $e->getMessage(),
        ];
    }
}

function Watchdog_deactivate()
{
    try {
        Capsule::schema()->dropIfExists('wd_audit');
        Capsule::schema()->dropIfExists('wd_whitelist');

        Capsule::table('tbladdonmodules')
            ->where('module', 'Watchdog')
            ->delete();

        return [
            'status' => 'success',
            'description' => 'Watchdog deactivated successfully.',
        ];
    } catch (\Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Unable to deactivate Watchdog: ' . $e->getMessage(),
        ];
    }
}

function Watchdog_upgrade($vars)
{
    // Reserved for future schema migrations.
    return ['status' => 'success'];
}

function Watchdog_output($vars)
{
    $smarty = new Smarty();
    $smarty->caching = false;
    $smarty->compile_dir = $GLOBALS['templates_compiledir'];
    $smarty->setTemplateDir([dirname(__FILE__) . '/templates/Admin']);
    $smarty->assign('modulelink', $vars['modulelink']);
    $smarty->assign('_ADDONLANG', $vars['_lang']);
    $smarty->assign('csrfToken', function_exists('generate_token') ? generate_token('plain') : '');

    $view = isset($_GET['view']) ? (string) $_GET['view'] : 'Dashboard';

    switch ($view) {
        case 'Whitelist':
            $data = new Whitelist();
            $smarty->assign('data', $data->listing());
            break;

        case 'Settings':
            $data = new Settings();
            $smarty->assign('data', $data->listing());
            break;

        case 'Dashboard':
        default:
            $data = new Dashboard();
            $smarty->assign('data', $data->listing());
            break;
    }

    $smarty->display(__DIR__ . '/templates/Admin/Header.tpl');

    if ($view === 'Whitelist') {
        $smarty->display(__DIR__ . '/templates/Admin/Whitelist.tpl');
    } elseif ($view === 'Settings') {
        $smarty->display(__DIR__ . '/templates/Admin/Settings.tpl');
    } else {
        $smarty->display(__DIR__ . '/templates/Admin/Dashboard.tpl');
    }

    $smarty->display(__DIR__ . '/templates/Admin/Footer.tpl');
}
