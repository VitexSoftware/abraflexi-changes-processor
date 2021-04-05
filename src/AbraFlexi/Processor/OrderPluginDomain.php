<?php
/**
 * System.Spoje.Net - Domain.com order form item
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2019 Spoje.Net
 */

namespace AbraFlexi\Processor;

use Ease\Html\InputTextTag;
use Ease\TWB\Form;
use Ease\TWB\Label;
use AbraFlexi\Stitek;
use Phois\Whois\Whois;
use SpojeNet\System\ui\OrderFormHtml;

/**
 * Description of OrderPluginDomain
 *
 * @author vitex
 */
class OrderPluginDomain extends OrderPlugin
{
    /**
     * Regex for checking domainname
     * @var string 
     */
    public $tlds = "/^[-a-z0-9]{1,63}\.(ac\.nz|co\.nz|geek\.nz|gen\.nz|kiwi\.nz|maori\.nz|net\.nz|org\.nz|school\.nz|ae|ae\.org|com\.af|asia|asn\.au|auz\.info|auz\.net|com\.au|id\.au|net\.au|org\.au|auz\.biz|az|com\.az|int\.az|net\.az|org\.az|pp\.az|biz\.fj|com\.fj|info\.fj|name\.fj|net\.fj|org\.fj|pro\.fj|or\.id|biz\.id|co\.id|my\.id|web\.id|biz\.ki|com\.ki|info\.ki|ki|mobi\.ki|net\.ki|org\.ki|phone\.ki|biz\.pk|com\.pk|net\.pk|org\.pk|pk|web\.pk|cc|cn|com\.cn|net\.cn|org\.cn|co\.in|firm\.in|gen\.in|in|in\.net|ind\.in|net\.in|org\.in|co\.ir|ir|co\.jp|jp|jp\.net|ne\.jp|or\.jp|co\.kr|kr|ne\.kr|or\.kr|co\.th|in\.th|com\.bd|com\.hk|hk|idv\.hk|org\.hk|com\.jo|jo|com\.kz|kz|org\.kz|com\.lk|lk|org\.lk|com\.my|my|com\.nf|info\.nf|net\.nf|nf|web\.nf|com\.ph|ph|com\.ps|net\.ps|org\.ps|ps|com\.sa|com\.sb|net\.sb|org\.sb|com\.sg|edu\.sg|org\.sg|per\.sg|sg|com\.tw|tw|com\.vn|net\.vn|org\.vn|vn|cx|fm|io|la|mn|nu|qa|tk|tl|tm|to|tv|ws|academy|careers|education|training|bike|biz|cat|co|com|info|me|mobi|name|net|org|pro|tel|travel|xxx|blackfriday|clothing|diamonds|shoes|tattoo|voyage|build|builders|construction|contractors|equipment|glass|lighting|plumbing|repair|solutions|buzz|sexy|singles|support|cab|limo|camera|camp|gallery|graphics|guitars|hiphop|photo|photography|photos|pics|center|florist|institute|christmas|coffee|kitchen|menu|recipes|company|enterprises|holdings|management|ventures|computer|systems|technology|directory|guru|tips|wiki|domains|link|estate|international|land|onl|pw|today|ac\.im|co\.im|com\.im|im|ltd\.co\.im|net\.im|org\.im|plc\.co\.im|am|at|co\.at|or\.at|ba|be|bg|biz\.pl|com\.pl|info\.pl|net\.pl|org\.pl|pl|biz\.tr|com\.tr|info\.tr|tv\.tr|web\.tr|by|ch|co\.ee|ee|co\.gg|gg|co\.gl|com\.gl|co\.hu|hu|co\.il|org\.il|co\.je|je|co\.nl|nl|co\.no|no|co\.rs|in\.rs|rs|co\.uk|org\.uk|uk\.net|com\.de|de|com\.es|es|nom\.es|org\.es|com\.gr|gr|com\.hr|com\.mk|mk|com\.mt|net\.mt|org\.mt|com\.pt|pt|com\.ro|ro|com\.ru|net\.ru|ru|su|com\.ua|ua|cz|dk|eu|fi|fr|pm|re|tf|wf|yt|gb\.net|ie|is|it|li|lt|lu|lv|md|mp|se|se\.net|si|sk|ac|ag|co\.ag|com\.ag|net\.ag|nom\.ag|org\.ag|ai|com\.ai|com\.ar|as|biz\.pr|com\.pr|net\.pr|org\.pr|pr|biz\.tt|co\.tt|com\.tt|tt|bo|com\.bo|com\.br|net\.br|tv\.br|bs|com\.bs|bz|co\.bz|com\.bz|net\.bz|org\.bz|ca|cl|co\.cr|cr|co\.dm|dm|co\.gy|com\.gy|gy|co\.lc|com\.lc|lc|co\.ms|com\.ms|ms|org\.ms|co\.ni|com\.ni|co\.ve|com\.ve|co\.vi|com\.vi|com\.co|net\.co|nom\.co|com\.cu|cu|com\.do|do|com\.ec|ec|info\.ec|net\.ec|com\.gt|gt|com\.hn|hn|com\.ht|ht|net\.ht|org\.ht|com\.jm|com\.kn|kn|com\.mx|mx|com\.pa|com\.pe|pe|com\.py|com\.sv|com\.uy|uy|com\.vc|net\.vc|org\.vc|vc|gd|gs|north\.am|south\.am|us|us\.org|sx|tc|vg|cd|cg|cm|co\.cm|com\.cm|net\.cm|co\.ke|or\.ke|co\.mg|com\.mg|mg|net\.mg|org\.mg|co\.mw|com\.mw|coop\.mw|mw|co\.na|com\.na|na|org\.na|co\.ug|ug|co\.za|com\.ly|ly|com\.ng|ng|com\.sc|sc|mu|rw|sh|so|st|club|kiwi|uno|email|ruhr)$/i";

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = '/^DOMENA.*/';

    /**
     *
     * @var \Subreg\Client 
     */
    private $subreg = null;

    /**
     * Add Product Fields to Form
     *
     * @param OrderFormHtml $form
     * 
     * @return Form
     */
    public function formFields($form)
    {
        parent::formFields($form);
        $currency = 'CZK';
        $form->addInput(new InputTextTag('domain',
                $form->order->getDataValue('domain')), _('Domain name'), '',
            _('Domain Name you requested to pay'));

        $price = $form->order->cenik->getDataValue('cenaZaklVcDph');
        if (floatval($price)) {
            $form->addItem(new Label('info',
                    sprintf(_('Domain registraton price: %s %s'), round($price),
                        $currency)));
        }

        return $form;
    }

    /**
     * Control Plugin fields
     *
     * @param OrderItem $order
     * 
     * @return boolean
     */
    public function controlFields($order)
    {
        return parent::controlFields($order) && $this->checkDomain($order->getDataValue('domain'));
    }

    /**
     * Check Domain Validity
     *
     * @param string $domain
     * 
     * @return boolean
     */
    public function checkDomain($domain)
    {
        $result = true;

        if (strlen($domain)) {
            if (!preg_match($this->tlds, $domain)) {
                $this->addStatusMessage(sprintf(_('Domain name %s is not valid'),
                        $domain), 'warning');
                $result = false;
            }
        } else {
            $this->addStatusMessage(_('Please enter Domain name'), 'info');
            $result = false;
        }

        return $result;
    }

    /**
     * Check if domain is free for registration
     *
     * @param Whois $domain
     * 
     * @return boolean availbility
     */
    public function isFree($domain)
    {
        $domain = new Whois($domain);
        return $domain->isAvailable();
    }

    /**
     * Make OrderItem from FormData
     *
     * @param OrderItem $order
     * 
     * @return array
     */
    public function processFields($order)
    {
        $orderItemData = parent::processFields($order);

        if ($this->isFree($order->getDataValue('domain'))) {
            $orderItemData['nazev'] = sprintf(_('Domain %s Registration'),
                $order->getDataValue('domain'));
        } else {
            $orderItemData['nazev'] = sprintf(_('Domain %s Renew'),
                $order->getDataValue('domain'));
        }
        $orderItemData['poznam'] = $order->getDataValue('domain');
        return $orderItemData;
    }

    public function renewDomain(string $domain, int $years = 1)
    {
        return $this->subreger()->renewDomain($domain, $years);
    }

    /**
     * Subreg ApiClient
     * 
     * @return SubregAPI
     */
    public function subreger()
    {
        if (is_null($this->subreg)) {
            $this->subreg = new SubregAPI();
        }
        return $this->subreg;
    }

    /**
     * Do this when item is settled
     * 
     * @param FakturaVydana $invoice
     * 
     * @return type Description
     */
    public function settled($invoice)
    {
        $itemsOfInterest = $this->getItemsOfInterest($invoice);
        $price           = 0.0;
        if (!empty($itemsOfInterest) && (array_key_exists('API',
                Stitek::listToArray($invoice->getDataValue('stitky'))))) {
            foreach ($itemsOfInterest as $creditItemData) {
                $domain = $creditItemData['nazev'];
                if (preg_match($this->tlds, $domain)) {
                    $this->addStatusMessage( sprintf( _('Domain %s renewal'), $domain ),
                        $this->subreger()->setAutorenew($domain, 'RENEWONCE') ? 'success' : 'error');
                } else {
                    $this->addStatusMessage(sprintf(_('Domain name %s is not valid'),
                            $domain), 'error');
                }

                if (isset($creditItemData['mnozMj'])) {
                    $itemPrice = $creditItemData['mnozMj'] * $creditItemData['cenaMj'];
                } else {
                    $itemPrice = floatval($creditItemData['cenaMj']);
                }
                $price += $itemPrice;
            }
        }

        return parent::settled($invoice);
    }
}
