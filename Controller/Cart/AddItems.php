<?php

namespace Firework\Firework\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItem;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Model\Session;

class AddItems extends Action implements HttpPostActionInterface
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Cart
     */
    protected $cart;
    
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var QuoteItem
     */
    protected $quoteItem;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    protected $quoteIdToMaskedQuoteId;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Created a construct object
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Json $json
     * @param QuoteItem $quoteItem
     * @param JsonFactory $resultJsonFactory
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManager
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        Json $json,
        QuoteItem $quoteItem,
        JsonFactory $resultJsonFactory,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Session $checkoutSession
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->json = $json;
        $this->quoteItem = $quoteItem;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * Add to Cart product and return Json encode response
     *
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getContent();
        $allData = $this->getJsonDecode($data);

        $resultJson = $this->resultJsonFactory->create();
        $result = [];

        if ($this->getRequest()->getParam('is_new_quote')) {
            $this->_checkoutSession->clearQuote();
        }

        foreach ($allData as $product_item){
            if (!isset($product_item['product']) || !isset($product_item['qty'])) {
                $result = [
                    'success' => false,
                    'message' => __('Required parameter are missing.')
                ];
                return $resultJson->setData($result);
            }
        }

        try {

            foreach ($allData as $productData){
                $product = $this->productRepository->get($productData['product']);                
                $params = $this->checkProductType($product, $productData['product'], $productData);
                $this->cart->addProduct($product, $params);
            }

            $this->cart->save();

            $items = $this->cart->getQuote()->getAllVisibleItems();
            $latest = [];
            foreach ($items as $item) {
                $itemId = $item->getItemId();
                $itemFactory = $this->quoteItem->create()->load($itemId);
                $latest[$itemId] = $itemFactory->getUpdatedAt();
            }

            $latestItemIds = array_keys($latest, max($latest));

            if (count($latestItemIds)) {
                $result = [
                    'success' => true
                ];
                foreach ($latestItemIds as $latestItemId) {
                    $quoteItemData = $this->quoteItem->create()->load($latestItemId);
                    $quoteMaskId = $this->getQuoteMaskId($quoteItemData->getQuoteId());
                    
                    $data = $quoteItemData->getData();
                    
		    $data['mask_id'] = $quoteMaskId;
                    
		    $result['data'][] = $data;
                }

                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDuration(86400)
                    ->setPath($this->sessionManager->getCookiePath())
                    ->setDomain($this->sessionManager->getCookieDomain());

                $this->cookieManager->setPublicCookie(
                    'firework_item_added',
                    true,
                    $metadata
                );
            } else {
                $result = [
                    'success' => true,
                    'message' => __('You added item data not found.')
                ];
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        
        return $resultJson->setData($result);
    }

     /**
      * Check Product Type
      *
      * @param object $product
      * @param string $productSku
      * @param array $data
      * @return void
      */
    public function checkProductType($product, $productSku, $data)
    {
        $productTypeId = $product->getTypeId();
        $productParams = [];

        if ($productTypeId == 'simple') {
            $productParams = $this->addSimpleAndVirtul($productSku, $data);
        } elseif ($productTypeId == 'virtual') {
            $productParams = $this->addSimpleAndVirtul($productSku, $data);
        } elseif ($productTypeId == 'bundle') {
            $productParams = $this->addBundleProduct($productSku, $data);
        } elseif ($productTypeId == 'configurable') {
            $productParams = $this->addConfigurableProduct($productSku, $data);
        } elseif ($productTypeId == 'downloadable') {
            $productParams = $this->addDownloadableProduct($productSku, $data);
        } elseif ($productTypeId == 'grouped') {
            $productParams = $this->addGroupedProduct($productSku, $data);
        }

        return $productParams;
    }

     /**
      * Add Simple And Virtual product Params
      *
      * @param string $productSku
      * @param array $data
      * @return array
      */
    public function addSimpleAndVirtul($productSku, $data)
    {
        //$allData = $this->getJsonDecode($data);

        if (isset($data['options'])) {
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $productSku,
                'options' => $data['options'],
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1
            ];
        } else {
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $productSku,
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1
            ];
        }

        return $params;
    }

    /**
     * Add Configurable Product Params
     *
     * @param string $productSku
     * @param array $data
     * @return void
     */
    public function addConfigurableProduct($productSku, $data)
    {
        //$allData = $this->getJsonDecode($data);
        $params = [];
        if (isset($data['options']) && isset($data['super_attrs'])) {
            $params = [
                'product' => $productSku,
                'super_attribute' => $data['super_attrs'],
                'options' => $data['options'],
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1
            ];
        } elseif (isset($data['super_attrs'])) {
            $params = [
                'product' => $productSku,
                'super_attribute' => $data['super_attrs'],
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1
            ];
        }

        return $params;
    }

    /**
     * Add Bundle Product Params
     *
     * @param string $productSku
     * @param array $data
     * @return void
     */
    public function addBundleProduct($productSku, $data)
    {
        //$allData = $this->getJsonDecode($data);
        if ($data['bundle_option']) {
            $params = [
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1,
                'product' => $productSku,
                'bundle_option' => $data['bundle_option'],
                'bundle_option_qty' => $data['bundle_option_qty']
            ];
        }

        return $params;
    }

    /**
     * Add Grouped Product
     *
     * @param string $productSku
     * @param array $data
     * @return array
     */
    public function addGroupedProduct($productSku, $data)
    {
        //$allData = $this->getJsonDecode($data);
        // Key is child product id and value is product qty
        if (isset($data['super_group'])) {
            $params = [
                'product' => $productSku,
                'super_group' => $data['super_group']
            ];
        }

        return $params;
    }

    /**
     * Add Downloadable Product
     *
     * @param sring $productSku
     * @param array $data
     * @return array
     */
    public function addDownloadableProduct($productSku, $data)
    {
        //$allData = $this->getJsonDecode($data);

        if (isset($data['links']) && isset($data['options'])) {
            $params = [
                'product' => $productSku,
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1,
                'links' => $data['links'],
                'options' => $data['options']
            ];
        } elseif (isset($data['links'])) {
            $params = [
                'product' => $productSku,
                'qty' => (isset($data['qty'])) ? $data['qty'] : 1,
                'links' => $data['links']
            ];
        }

        return $params;
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
     * Will decode json data
     *
     * @param array $data
     * @return void
     */
    public function getJsonDecode($data)
    {
        return $this->json->unserialize($data);
    }

    /**
     * Will get quote mask id
     *
     * @param string $quoteId
     * @return string
     */
    public function getQuoteMaskId($quoteId)
    {
        $maskedId = null;
        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
            if (!$maskedId) {
                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $quoteIdMask->setQuoteId($quoteId)->save();
                return $quoteIdMask->getMaskedId();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            return '';
        }

        return $maskedId;
    }
}
