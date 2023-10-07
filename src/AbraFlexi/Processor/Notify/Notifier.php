<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace AbraFlexi\Processor\Notify;

/**
 *
 * @author vitex
 */
interface Notifier
{
    /**
     *
     * @param \AbraFlexi\Processor\Plugin $handler
     */
    function notify(\AbraFlexi\Processor\Plugin $handler);
}
