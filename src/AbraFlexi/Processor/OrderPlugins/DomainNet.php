<?php
/**
 * System.Spoje.Net - Domain.net order form item
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
class DomainNet extends \SpojeNet\System\OrderPluginDomain
{
    /**
     * Regex for checking domainname
     * @var string
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.net$/i";

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'DOMENANET';


    
    
    /**
     *
     * @var type
     */
    public $fields = [];

    public function setModuleName()
    {
        $this->name = _('Domain .NET');
    }

}
