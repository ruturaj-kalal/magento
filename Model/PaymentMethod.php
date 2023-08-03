<?php

namespace Firework\Firework\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;

/**
 * Firework Custom Payment Method Model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod {

    protected $_code = "firework";
    protected $_isOffline = true;

    protected $_request;
    
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        $this->_request = $request;
    }

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        if ($this->_request->getParam('is_firework_checkout')) {
            return true;
        }
        return false;
    }
}