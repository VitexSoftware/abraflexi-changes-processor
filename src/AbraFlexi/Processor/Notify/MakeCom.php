<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AbraFlexi\Processor\Notify;

/**
 * Description of MakeCom
 *
 * @author vitex
 */
class MakeCom extends \Ease\Sand implements Notifier {

    /**
     * MakeCom endpoint 
     * @var string
     */
    private $endPoint = '';

    /**
     * MakeCOM Notifier 
     */
    public function __construct() {
        $this->endPoint = \Ease\Functions::cfg('FOREGIN_ENPOINT');
        if(empty($this->endPoint)){
            $this->addStatusMessage(_('FOREGIN_ENPOINT not defined. Disabling notification to Make.com'), 'warning');
        }
    }

    /**
     * Accept or skip several metastates of interest
     * 
     * @param \AbraFlexi\Processor\Plugin $engine
     * 
     * @return boolean
     */
    function interested($engine) {
//        $want = false;
//        switch ($engine->getMetaState()) {
//            case 'settle':
//                $want = false;
//                break;
//
//            default:
//                break;
//        }
//        return $want;
        
        return $engine->isSettled();
    }

    /**
     * 
     * @param \AbraFlexi\Processor\Plugin $engine
     */
    function notify(\AbraFlexi\Processor\Plugin $engine) {
        $this->addStatusMessage('Notify MakeCOM', 'debug');

        $record = $engine->getData();

        if (self::interested($engine)) {
            $id = $engine->changeid;
            $data['winstrom']['changes'][$id]['premodified'] = 'OK';

//    $engine->loadFromAbraFlexi(empty($record['external-ids']) ? (int)$record['id'] : current($record['external-ids']) );
//    $data['winstrom']['changes'][$id]['id'] = $engine->getRecordID();
            $data['winstrom']['changes'][$id]['id'] = $record['id'];

            if ($engine->extids) {
                $data['winstrom']['changes'][$id]['external-ids'] = $engine->extids;
            }
            $data['winstrom']['changes'][$id]['external-ids'][] = 'ext:meta:' . $engine->getMetaState();

            $data['winstrom']['changes'][$id]['modified'] = true;

            $content = json_encode($data);

            $curl = curl_init($this->endPoint);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                    array("Content-type: application/json"));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

            $json_response = curl_exec($curl);

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            $engine->addStatusMessage('MakeCOM: '.$json_response, $status == 200 ? 'success' : 'error' );
        }
    }

//put your code here
}
