<?php

namespace Firework\Firework\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class StoreSave implements ObserverInterface
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
     * @param Logger $logger
     * @param WriterInterface $configWriter
     */
    public function __construct(
        Logger $logger,
        WriterInterface $configWriter
    ) {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
    }

    /**
     * Save Store and Genrate business id and business store id columns
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $storeData = $observer->getStore();
        $websiteId = $storeData->getWebsiteId();
        $storeId = $storeData->getId();
        if (isset($storeId)) {
            $this->configWriter->save(
                'firework/general/business_store_id',
                null,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                $storeId
            );
            $this->configWriter->save(
                'firework/general/business_id',
                null,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
                $storeId
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
