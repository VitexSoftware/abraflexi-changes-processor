<?php
/**
 * System.Spoje.Net - Domain.com order form item
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

namespace AbraFlexi\Processor\OrderPlugins;

use SpojeNet\System\OrderPluginDomain;

/**
 * Description of VoIP
 *
 * @author vitex
 */
class DomainCom extends OrderPluginDomain
{
    /**
     * Regex for checking domainname
     * @var string
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.com$/i";

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'DOMENACOM';

    /**
     *
     * @var type
     */
    public $fields = [];

    public function setModuleName()
    {
        $this->name = _('Domain .COM');
    }
}
