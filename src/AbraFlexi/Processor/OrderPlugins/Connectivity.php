<?php
namespace AbraFlexi\Processor\OrderPlugins;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Connectivity
 *
 * @author vitex
 */
class Connectivity {

    //put your code here


    public function __construct() {
        $lmsId = $adrHelper->getExternalID('lms.cstmr');
        if (!empty($lmsId) && !$zewlScore) {
            $sysStr = constant('DASHBOARD_SCRIPTS') . "reset-dashboards.py  $lmsId";
            shell_exec("sudo $sysStr");
            $this->addStatusMessage($sysStr, 'debug');
            $this->addStatusMessage(shell_exec('ssh sysifos.spoje.net \'sudo /root/bin/make-dashboard\''));
        }
    }

}
