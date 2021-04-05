<?php
/**
 * System.Spoje.Net - VoIP Order Plugin.
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
class VoIPaccount extends \SpojeNet\System\OrderPlugin
{
    /**
     * Name of Product/Service
     * @var string
     */
    public $name = null;

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = 'ACCOUNT_VOIP';

    /**
     *
     * @var type
     */
    public $fields = [];

    /**
     * VoIP Credir Order Plugin
     */
    public function setModuleName()
    {
        $this->name = _('VoIP Account');
    }

}