<?php

namespace WHMCS\Module\Addon\Watchdog;

use WHMCS\Database\Capsule;

class Dashboard
{
    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inspect'])) {
            $this->inspect();
        }
    }

    public function listing()
    {
        $output = [
            'fileSystem' => [],
            'statistics' => [],
        ];

        $rows = Capsule::table('wd_audit')
            ->select(['path', 'detected', 'expected', 'status'])
            ->orderBy('path')
            ->get();

        foreach ($rows as $row) {
            $status = (int) $row->status;
            $output['fileSystem'][$status][] = $row;
        }

        $stats = Capsule::table('wd_audit')
            ->selectRaw('status, COUNT(path) AS total')
            ->groupBy('status')
            ->get();

        foreach ($stats as $row) {
            $output['statistics'][(int) $row->status] = (int) $row->total;
        }

        return $output;
    }

    private function inspect()
    {
        if (!function_exists('check_token') || !check_token('WHMCS.admin.default')) {
            http_response_code(403);
            exit('Invalid security token');
        }

        $path = isset($_POST['path']) ? trim((string) $_POST['path']) : '';
        if ($path === '' || strlen($path) > 260) {
            http_response_code(400);
            exit('Invalid path');
        }

        $row = Capsule::table('wd_audit')
            ->select(['path', 'detected', 'expected', 'status'])
            ->where('path', $path)
            ->first();

        if (!$row) {
            http_response_code(404);
            exit('Finding not found');
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($row, JSON_UNESCAPED_SLASHES);
        exit;
    }
}
