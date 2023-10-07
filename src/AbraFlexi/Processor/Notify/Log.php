<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AbraFlexi\Processor\Notify;

/**
 * Description of Log
 *
 * @author vitex
 */
class Log extends \Ease\Sand implements Notifier
{
    /**
     * Notify into log
     * @param \AbraFlexi\Processor\Plugin $handler
     */
    function notify(\AbraFlexi\Processor\Plugin $handler)
    {
        $this->addStatusMessage(json_encode($handler->getData()), 'debug');
    }
}
