<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/*
  +------------+--------------+------+-----+---------------------+----------------+
  | Field      | Type         | Null | Key | Default             | Extra          |
  +------------+--------------+------+-----+---------------------+----------------+
  | id         | int(11)      | NO   | PRI | NULL                | auto_increment |
  | uri        | varchar(200) | NO   | MUL | NULL                |                |
  | meta       | varchar(40)  | NO   |     | NULL                |                |
  | discovered | datetime     | NO   | MUL | current_timestamp() |                |
  | processed  | datetime     | YES  |     | NULL                |                |
  +------------+--------------+------+-----+---------------------+----------------+
 */

namespace AbraFlexi\Processor\Notify;

/**
 * Description of Log
 *
 * @author vitex
 */
class Db extends \Ease\SQL\Engine implements Notifier {

    public $myTable = 'meta';

    /**
     * Save record into meta table
     * 
     * @param \AbraFlexi\Processor\Plugin $handler
     */
    function notify(\AbraFlexi\Processor\Plugin $handler) {
        $handler->setDataValue('external-ids', null);
        $this->addStatusMessage('Notify to Database', is_integer($this->insertToSQL(['uri' => $handler->getApiURL(), 'changeid' => $handler->changeid, 'meta' => $handler->getMetaState()])) ? 'success' : 'error' );
    }

}
