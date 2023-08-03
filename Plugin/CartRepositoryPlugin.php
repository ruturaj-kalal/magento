<?php
namespace Firework\Firework\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;

class CartRepositoryPlugin
{
    /**
     * @var CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @param CartExtensionFactory $cartExtensionFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        CartExtensionFactory $cartExtensionFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(CartRepositoryInterface $cartRepository, CartInterface $cart)
    {
        if ($quoteId = $cart->getId()) {
            try {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'quote_id');
                $maskedId = $quoteIdMask->getMaskedId();
            } catch (\Exception $e) {
                $maskedId = '';
            }
            $extensionAttributes = $cart->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->cartExtensionFactory->create();
            $extensionAttributes->setMaskId($maskedId);
            $cart->setExtensionAttributes($extensionAttributes);
        }

        return $cart;
    }
}
