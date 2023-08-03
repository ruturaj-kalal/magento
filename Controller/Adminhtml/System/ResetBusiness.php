<?php
namespace Firework\Firework\Controller\Adminhtml\System;

use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Firework\Firework\Helper\Data as FireworkHelper;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;

class ResetBusiness extends Action implements HttpPostActionInterface
{
    private const ACCESS_TOKEN = 'firework/general/access_token';
    private const REFRESH_TOKEN = 'firework/general/refresh_token';
    private const BUSINESS_STORE_ID = 'firework/general/business_store_id';

    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $configWriter;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Reset Business Construct
     *
     * @param Context $context
     * @param FireworkHelper $fireworkHelper
     * @param Data $jsonHelper
     * @param WriterInterface $configWriter
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        FireworkHelper $fireworkHelper,
        Data $jsonHelper,
        WriterInterface $configWriter,
        ManagerInterface $messageManager
    ) {
        $this->fireworkHelper = $fireworkHelper;
        $this->jsonHelper = $jsonHelper;
        $this->configWriter = $configWriter;
        $this->messageManager = $messageManager;
        parent::__construct(
            $context
        );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $storeData = $this->fireworkHelper->getStoreAndWebsite($data);

        $this->configWriter->save(
            FireworkHelper::BUSINESS_ID,
            null,
            $storeData['scope'],
            $storeData['store_id']
        );
        $this->configWriter->save(
            self::BUSINESS_STORE_ID,
            null,
            $storeData['scope'],
            $storeData['store_id']
        );
        $response['message'] = 'successUrl';
        $this->messageManager->addSuccess(
            __("Successfully cleared Business Data")
        );
        $this->fireworkHelper->flushCache();
        return $this->jsonResponse($response);
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
