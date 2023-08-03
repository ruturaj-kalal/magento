<?php
namespace Firework\Firework\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Firework\Firework\Helper\Data as FireworkHelper;

/**
 * Connect Button Click will call API and send OAuth data to API
 */
class ConnectButton extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Firework_Firework::system/config/connectbutton.phtml';

    /**
     * @param Context $context
     * @param FireworkHelper $fireworkHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FireworkHelper $fireworkHelper,
        array $data = []
    ) {
        $this->fireworkHelper = $fireworkHelper;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Controller action URL here
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('firework/system/connectbutton', ['_secure' => true]);
    }

    /**
     * Create a HTML of Connect FireWork Button
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        );
        $integrationToken = $this->fireworkHelper->getIntegrationToken();
        
        if (($integrationToken != null) && !empty($integrationToken)) {
            $button->setData(
                [
                    'id' => 'connectbutton',
                    'label' => __('Connect FireWork'),
                ]
            );
        } else {
            $button->setData(
                [
                    'id' => 'connectbutton',
                    'disabled' => true,
                    'label' => __('Connect FireWork'),
                ]
            );
        }
        return $button->toHtml();
    }

    /**
     * Get Store Data using helper
     *
     * @return array
     */
    public function getCurrentStoreAndWebsite()
    {
        return $this->fireworkHelper->getScopeId();
    }
}
