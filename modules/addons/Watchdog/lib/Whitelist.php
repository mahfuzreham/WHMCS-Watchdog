<?php

namespace WHMCS\Module\Addon\Watchdog;

use WHMCS\Database\Capsule;

class Whitelist
{
    public function listing()
    {
        return Capsule::table('wd_whitelist')
            ->select(['path', 'notes'])
            ->orderBy('path')
            ->get();
    }
}
