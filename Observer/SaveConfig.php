<?php

namespace Firework\Firework\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Api\StoreRepositoryInterface;

class SaveConfig implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $configWriter;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepositoryInterface;

    /**
     * @param Logger $logger
     * @param WriterInterface $configWriter
     * @param StoreRepositoryInterface $storeRepositoryInterface
     */
    public function __construct(
        Logger $logger,
        WriterInterface $configWriter,
        StoreRepositoryInterface $storeRepositoryInterface
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->storeRepositoryInterface = $storeRepositoryInterface;
    }

    /**
     * Save Config and Genrate business id and business store id columns
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $stores = $this->storeRepositoryInterface->getList();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            $websiteId = $store->getWebsiteId();
            if (isset($storeId)) {
                $this->configWriter->save(
                    'firework/general/business_store_id',
                    null,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
                $this->configWriter->save(
                    'firework/general/business_id',
                    null,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
            }
            if (isset($websiteId)) {
                $this->configWriter->save(
                    'firework/general/business_store_id',
                    null,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                    $websiteId
                );
                $this->configWriter->save(
                    'firework/general/business_id',
                    null,
                    \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES,
                    $websiteId
                );
            }
        }
    }
}
