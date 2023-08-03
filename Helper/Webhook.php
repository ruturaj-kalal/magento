<?php
namespace Firework\Firework\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Helper Data
 */
class Webhook extends AbstractHelper
{
    public const WEBHOOK_ENABLE = 'firework/webhook/enable';
    public const WEBHOOK_ENDPOINT = 'firework/webhook/endpoint';
    public const WEBHOOK_BULKSIZE = 'firework/webhook/bulk';
    public const WEBHOOK_LOG_CLEAN_DAYS = 'firework/webhook/log_clean_days';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;


    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Get Oauth User Token
     *
     * @return string
     */
    public function isEnable()
    {
        return $this->scopeConfig->getValue(
            self::WEBHOOK_ENABLE,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Oauth User Token
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->scopeConfig->getValue(
            self::WEBHOOK_ENDPOINT,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Oauth User Token
     *
     * @return string
     */
    public function getBulkSize()
    {
        return $this->scopeConfig->getValue(
            self::WEBHOOK_BULKSIZE,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Oauth User Token
     *
     * @return string
     */
    public function getLogCleanDays()
    {
        return $this->scopeConfig->getValue(
            self::WEBHOOK_LOG_CLEAN_DAYS,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}
