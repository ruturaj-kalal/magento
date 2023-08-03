<?php
namespace Firework\Firework\Plugin\Checkout\CustomerData;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;

class Cart
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;
    
    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    protected $quoteIdToMaskedQuoteId;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
    }
    
    /**
     * @inheritdoc
     *
     * @param \Magento\Checkout\CustomerData\Cart $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(
        \Magento\Checkout\CustomerData\Cart $subject,
        $result
    ) {
        $quote = $this->getQuote();

        if ($quote->getId()) {
            $result['quote'] = [
                'quote_id' => $quote->getId(),
                'customer_id' => $quote->getCustomerId(),
                'mask_id' => $this->getQuoteMaskId($quote->getId())
            ];
        }

        return $result;
    }

    /**
     * Get active quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * get Masked id by Quote Id
     *
     * @return string|null
     * @throws LocalizedException
     */
    public function getQuoteMaskId($quoteId)
    {
        $maskedId = null;
        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (NoSuchEntityException $exception) {
            
        }
 
        return $maskedId;
    }
}