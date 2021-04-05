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
class DomainNl extends \SpojeNet\System\OrderPluginDomain
{
    /**
     * Regex for checking domainname
     * @var string
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.nl$/i";

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'DOMENANL';

    /**
     *
     * @var type
     */
    public $fields = [];

    public function setModuleName()
    {
        $this->name = _('Domain .NL');
    }

}
