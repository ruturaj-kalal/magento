<?php
namespace Firework\Firework\Block\Adminhtml\Firework;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Firework\Firework\Helper\Data as FireworkHelper;

/**
 * Firework Dashboard class
 */
class Dashboard extends Template
{
    public const OAUTHAPP = 'magento';
    public const IFRAME_SRC = 'https://business.firework.com/integration';

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
     * Return Access Token
     *
     * @return string
     */
    public function getAccessToken()
    {
        $storeInfo = $this->fireworkHelper->getScopeId();
        return $this->fireworkHelper->getAccessToken($storeInfo['scope'], $storeInfo['store_id']);
    }

    /**
     * Return Bussiness Store Id
     *
     * @return string
     */
    public function getBussinessStoreId()
    {
        $storeInfo = $this->fireworkHelper->getScopeId();
        return $this->fireworkHelper->getBussinessStoreId(
            $storeInfo['scope'],
            $storeInfo['store_id']
        );
    }

    /**
     * Return Oauth Bussiness Store Id
     *
     * @return string
     */
    public function getBussinessId()
    {
        $storeInfo = $this->fireworkHelper->getScopeId();
        return $this->fireworkHelper->getBussinessId(
            $storeInfo['scope'],
            $storeInfo['store_id']
        );
    }

    /**
     * Set Oauth App
     *
     * @return string
     */
    public function setOauthApp()
    {
        return self::OAUTHAPP;
    }

    /**
     * Set Iframe Src
     *
     * @return string
     */
    public function setIframeSrc()
    {
        return self::IFRAME_SRC;
    }
}
