<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AbraFlexi\Processor;

/**
 * Description of Meta
 *
 * @author vitex
 */
class Meta extends Engine {

    /**
     * We work with table meta
     * @var string
     */
    public $myTable = 'meta';

    /**
     * Webhook Processor lockfile
     * @var string 
     */
    protected $lockfile = '/tmp/meta.lock';

    public function unprocessed() {
        return $this->listingQuery()->where('processed IS NULL')->orderBy('id');
    }

    public function firstUnprocessed() {
        return $this->unprocessed()->limit(1);
    }

    public function handle($meta) {
        $components = parse_url($meta['uri']);
        $pathParts = explode('/', $components['path']);
        $meta['documentID'] = urldecode($pathParts[4]);
        $meta['subject'] = $pathParts[3];
        $meta['company'] = $pathParts[2];
        return array_merge($meta, $components);
    }

    public function processMetas() {
        foreach ($this->unprocessed() as $meta) {
            print_r($this->handle($meta));
        }
    }

}
