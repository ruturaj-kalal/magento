<?php
namespace Firework\Firework\Cron;

use Firework\Firework\Model\Config\Source\Status;
use Firework\Firework\Model\WebhookFactory;
use Firework\Firework\Helper\Webhook as WebhookHelper;
use Firework\Firework\Helper\Data as FireworkHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Sync
{
    /**
     * @var WebhookFactory
     */
    protected $webhookFactory;

    /**
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WebhookFactory $webhookFactory
     * @param WebhookHelper $webhookHelper
     * @param FireworkHelper $fireworkHelper
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param PriceHelper $priceHelper
     * @param CollectionFactory $collectionFactory
     * @param Json $json
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        WebhookFactory $webhookFactory,
        WebhookHelper $webhookHelper,
        FireworkHelper $fireworkHelper,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper,
        CollectionFactory $collectionFactory,
        Json $json,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->webhookFactory = $webhookFactory;
        $this->webhookHelper = $webhookHelper;
        $this->fireworkHelper = $fireworkHelper;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->collectionFactory = $collectionFactory;
        $this->json = $json;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Product Data Sync Using Cron
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->webhookHelper->isEnable() || !$this->webhookHelper->getApiEndpoint()) {
            return $this;
        }

        // print request and response log.
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/firework-webhook.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $processedIds = [];
        $bulkSize = ($this->webhookHelper->getBulkSize())? : 50;

        $collection = $this->webhookFactory->create()->getCollection()
                    ->addFieldToFilter('status', ['in' => [Status::STATUS_ENABLE, Status::STATUS_PROCESSING]])
                    ->setPageSize($bulkSize)->setCurPage(1);

        if ($collection->getSize()) {
            $logger->info($collection->getSize().' product(s) ready to sync.');
            foreach ($collection as $log) {
                try {
                    if ($log->getActionCode() == 'delete') {
                        $productId = $log->getProductId();
                        $productSku = $log->getProductSku();
                        $data = [
                            'action' => $log->getActionCode(),
                            'id' => $productId,
                            'sku' => $productSku
                        ];
                        $jsonData = $this->getJsonEncode($data);
                        $this->callWebhookApi($productId, $log->getId(), $jsonData);
                    } else {
                        $productId = $log->getProductId();
                        $productSku = $log->getProductSku();
                        try {
                            $product = $this->productRepository->getById($productId);
                            $jsonData = $this->getProductJsonConfig($product, $log->getActionCode());
                        } catch (\Exception $e) {
                            $data = [
                                'action' => 'delete',
                                'id' => $productId,
                                'sku' => $productSku
                            ];
                            $jsonData = $this->getJsonEncode($data);
                        }
                        
                        $this->callWebhookApi($productId, $log->getId(), $jsonData);
                    }
                } catch (\Exception $e) {
                    $logger->info('LOG ID: '.$log->getId().' ERROR ='. $e->getMessage());
                }
            }
        } else {
            $logger->info('Not any product to sync.');
        }

        return $this;
    }

    /**
     * Get Product Json Config
     *
     * @param [type] $product
     * @param [type] $action
     * @return array
     */
    public function getProductJsonConfig($product, $action)
    {
        $store = $this->storeManager->getStore();
        $productData = $this->checkProductType($product);
        $baseMediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $data = [
            'action' => $action,
            'id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'short_description' => $product->getShortDescription(),
            'description' => $product->getDescription(),
            'meta_title' => $product->getMetaTitle(),
            'meta_keyword' => $product->getMetaKeyword(),
            'meta_description' => $product->getMetaDescription(),
            'base_image' => ($product->getImage()) ? $baseMediaUrl . 'catalog/product' . $product->getImage() : '',
            'product_url' => $product->getProductUrl(),
            'price' => $this->getFomattedPrice($product->getPrice()),
            'special_price' => ($product->getSpecialPrice()) ? $this->getFomattedPrice($product->getPrice()) : '',
            'product_variations' => $productData['product_variations'],
            'options' => $productData['optionsData']
        ];

        return $this->getJsonEncode($data);
    }

    /**
     * Get Price Format
     *
     * @param [type] $amount
     * @return string
     */
    public function getFomattedPrice($amount)
    {
        return $this->priceHelper->currency($amount, true, false);
    }

    /**
     * Call Webhook API
     *
     * @param [type] $productId
     * @param [type] $logProductId
     * @param [type] $jsonData
     * @return void
     */
    public function callWebhookApi($productId, $logProductId, $jsonData)
    {
        $processedIds = [];
        $item = $this->webhookFactory->create()->load($logProductId);
        $accessToken = $this->fireworkHelper->getMagentoAccessToken();
        $adminUrl = $this->fireworkHelper->getAdminUrl();
        $error = false;
        try {
            if (!in_array($productId, $processedIds)) {
                $url = $this->webhookHelper->getApiEndpoint().$this->fireworkHelper->getBussinessStoreId();
                
                $headers = [
                    'content-type' => 'application/json',
                    'access_token' =>  $accessToken,
                    'admin_url' => $adminUrl
                ];

                // Initiate request
                $this->curl->setHeaders($headers);
                $this->curl->post($url, $jsonData);
                $response = $this->curl->getBody();
                $statusCode = $this->curl->getStatus();
                
                $processedIds[] = $productId;
            }
            if ($statusCode == 200) {
                $item->setStatus(Status::STATUS_COMPLETED)->save();
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error Curl', ['exception' => $e]);
        }
    }

    /**
     * Check Product Type
     *
     * @param [type] $product
     * @return array
     */
    public function checkProductType($product)
    {
        $productTypeId = $product->getTypeId();

        if ($productTypeId == 'simple') {
            $optionsData = $this->getProductsOptions($product);
            $productVariations = '';
        } elseif ($productTypeId == 'virtual') {
            $optionsData = $this->getProductsOptions($product);
            $productVariations = '';
        } elseif ($productTypeId == 'bundle') {
            $optionsData = $this->getBundleProductOptionsCollection($product);
            $productVariations = '';
        } elseif ($productTypeId == 'configurable') {
            $optionsData = $this->getProductsOptions($product);
            $productVariations = $this->getConfigurableAssociatedProduct($product);
        } elseif ($productTypeId == 'downloadable') {
            $optionsData = $this->getProductsOptions($product);
            $productVariations = '';
        } elseif ($productTypeId == 'grouped') {
            $productVariations = $this->getGroupedAssociatedProduct($product);
            $optionsData = '';
        }

        $productDatas = [
            'optionsData' => $optionsData,
            'product_variations' => $productVariations
        ];

        return $productDatas;
    }

    /**
     * Get simple,virtal and downloadable product custom options
     *
     * @param [type] $product
     * @return array|null
     */
    public function getProductsOptions($product)
    {
        try {
            $productOptions = $this->collectionFactory->create()->getProductOptions(
                $product->getEntityId(),
                $product->getStoreId(),
                false
            );

            $optionData = [];
            foreach ($productOptions as $key => $option) {
                $optionId = $option->getId();
                $optionType = $option->getType();
                if ($optionType === 'drop_down' ||
                    $optionType === 'radio' ||
                    $optionType === 'checkbox' ||
                    $optionType === 'multiple'
                    ) {
                    $optionData[$key] = $option->getData();
                    $optionValues = $product->getOptionById($optionId);
                    foreach ($optionValues->getValues() as $keyInner => $values) {
                        $optionData[$key][$keyInner] = $values->getData();
                    }
                } else {
                    $optionData[] = $option->getData();
                }
            }

            return $optionData;
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__('Such product doesn\'t exist'));
        }
    }

    /**
     * Get Bundle Product Options Collection
     *
     * @param [type] $product
     * @return array|null
     */
    public function getBundleProductOptionsCollection($product)
    {
        $optionsCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );
        $optionData = [];

        foreach ($optionsCollection as $key => $options) {
            $optionData[$key] = $options->getData();
            foreach ($selectionCollection as $keyInner => $proselection) {
                if ($options->getId() == $proselection->getOptionId()) {
                    $optionData[$key][$keyInner] = $proselection->getData();
                }
            }
        }

        return $optionData;
    }

    /**
     * Get Configurable Associated Product Data
     *
     * @param [type] $product
     * @return array|null
     */
    public function getConfigurableAssociatedProduct($product)
    {
        $productTypeInstance = $product->getTypeInstance();
        $usedProducts = $productTypeInstance->getUsedProducts($product);
        $productAssociated = [];

        foreach ($usedProducts as $child) {
            $productAssociated[] = $child->getData();
        }

        return $productAssociated;
    }

    /**
     * Get Grouped Associated Products
     *
     * @param [type] $product
     * @return array|null
     */
    public function getGroupedAssociatedProduct($product)
    {
        $childs = $product->getTypeInstance()->getAssociatedProducts($product);
        $productAssociated = [];

        foreach ($childs as $child) {
            $productAssociated[] = $child->getData();
        }

        return $productAssociated;
    }

    /**
     * Get Data and create Json encode
     *
     * @param array $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->json->serialize($data);
    }
 
    /**
     * Get Data and cerate Json decode
     *
     * @param array $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->json->unserialize($data);
    }
}
