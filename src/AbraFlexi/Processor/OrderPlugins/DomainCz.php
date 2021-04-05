<?php
/**
 * System.Spoje.Net - Domain.nl order form item
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

namespace SpojeNet\System\orderplugins;

use SpojeNet\System\OrderPluginDomain;

/**
 * Description of VoIP
 *
 * @author vitex
 */
class DomainCz extends OrderPluginDomain
{
    /**
     * Regex for checking domainname
     * @var string
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.cz$/i";

    /**
     * FlexiBee storage item Code
     * @var string regexp
     */
    public $productCode = 'DOMENACZ';

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
        $this->name = _('Domain .CZ');
    }
}
