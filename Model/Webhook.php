<?php
namespace Firework\Firework\Model;

use Firework\Firework\Api\Data\WebhookInterface;

class Webhook extends \Magento\Framework\Model\AbstractModel implements WebhookInterface
{
    /**
     * CMS page cache tag.
     */
    public const CACHE_TAG = 'firework_webhook';

    /**
     * @var string
     */
    protected $_cacheTag = 'firework_webhook';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'firework_webhook';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('Firework\Firework\Model\ResourceModel\Webhook');
    }
    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set EntityId.
     *
     * @param int $entityId
     * @return void
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get ProductId.
     *
     * @return varchar
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * Set ProductId.
     *
     * @param int $ProductId
     * @return void
     */
    public function setProductId($ProductId)
    {
        return $this->setData(self::PRODUCT_ID, $ProductId);
    }

    /**
     * Get Product Sku
     *
     * @return string
     */
    public function getProductSku()
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * Set Product Sku
     *
     * @param [type] $ProductSku
     * @return void
     */
    public function setProductSku($ProductSku)
    {
        return $this->setData(self::PRODUCT_SKU, $ProductSku);
    }

    /**
     * Get getActionCode.
     *
     * @return varchar
     */
    public function getActionCode()
    {
        return $this->getData(self::ACTION_CODE);
    }

    /**
     * Will set Action Code
     *
     * @param string $ActionCode
     * @return void
     */
    public function setActionCode($ActionCode)
    {
        return $this->setData(self::ACTION_CODE, $ActionCode);
    }

    /**
     * Get Status.
     *
     * @return varchar
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status.
     *
     * @param string $Status
     * @return void
     */
    public function setStatus($Status)
    {
        return $this->setData(self::STATUS, $Status);
    }

    /**
     * Get UpdatedAt.
     *
     * @return varchar
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATE_AT);
    }

    /**
     * Set UpdatedAt.
     *
     * @param string $UpdatedAt
     * @return void
     */
    public function setUpdatedAt($UpdatedAt)
    {
        return $this->setData(self::UPDATE_AT, $UpdatedAt);
    }

    /**
     * Get CreatedAt.
     *
     * @return varchar
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set CreatedAt.
     *
     * @param string $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
