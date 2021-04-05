<?php
/**
 * System.Spoje.Net - Domain.nl order form item
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

namespace SpojeNet\System\orderplugins;

/**
 * Description of VoIP
 *
 * @author vitex
 */
class DomainEu extends \SpojeNet\System\OrderPluginDomain
{
    /**
     * Regex for checking domainname
     * @var string
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.eu$/i";

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'DOMENAEU';


    /**
     *
     * @var type
     */
    public $fields = [];

    /**
     * 
     */
    public function setModuleName()
    {
        $this->name = _('Domain .EU');
    }
}
