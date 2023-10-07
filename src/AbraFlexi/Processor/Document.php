<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AbraFlexi\Processor;

/**
 * Description of Document
 *
 * @author vitex
 */
class Document extends \AbraFlexi\RW
{
    use \AbraFlexi\email;
    use \AbraFlexi\firma;
    use \AbraFlexi\getChanges;
    use \AbraFlexi\stitky;
    use \AbraFlexi\subItems;
    use \AbraFlexi\sum;

    /**
     *
     * @param sting $product AbraFlexi product CODE
     *
     * @return string|null
     */
    public static function getMetaForProduct($product)
    {
        $atributor = new \AbraFlexi\RW(null, ['evidence' => 'atribut']);
        $atribut = $atributor->getColumnsFromAbraFlexi(
            ['id', 'valString'],
            ['cenik' => $product, 'typAtributu' => 'code:META']
        );
        return empty($atribut) ? null : $atribut[0]['valString'];
    }

    /**
     *
     * @return \AbraFlexi\RW
     */
    public function getSubObjects()
    {
        $subEvidence = $this->getEvidence() . '-polozka';
        $subClass = '\\AbraFlexi\\' . str_replace(
            ' ',
            '',
            ucwords(str_replace('-', ' ', $subEvidence))
        );
        if (class_exists($subClass) === false) {
            $subClass = '\\AbraFlexi\\RW';
        }

        $subObjects = [];
        foreach ($this->getSubItems() as $subItemData) {
            $subObjects[$subItemData['id']] = new $subClass(
                $subItemData,
                ['evidence' => $subEvidence]
            );
        }
        return $subObjects;
    }
}
