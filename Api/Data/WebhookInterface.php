<?php
namespace Firework\Firework\Api\Data;

interface WebhookInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    public const ENTITY_ID = 'entity_id';
    public const PRODUCT_ID = 'product_id';
    public const PRODUCT_SKU = 'product_sku';
    public const ACTION_CODE = 'action_code';
    public const STATUS = 'status';
    public const UPDATE_AT = 'updated_at';
    public const CREATED_AT = 'created_at';

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();

     /**
      * Set EntityId
      *
      * @param int $entityId
      * @return void
      */
    public function setEntityId($entityId);

    /**
     * Get ProductId.
     *
     * @return integer
     */
    public function getProductId();

    /**
     * Set ProductId.
     *
     * @param int $ProductId
     * @return void
     */
    public function setProductId($ProductId);

    /**
     * Get product Sku
     *
     * @return string
     */
    public function getProductSku();

    /**
     * Set Product Sku
     *
     * @param string $ProductSku
     * @return void
     */
    public function setProductSku($ProductSku);

    /**
     * Get ActionCode.
     *
     * @return varchar
     */
    public function getActionCode();

    /**
     * Undocumented function
     *
     * @param varchar $ActionCode
     * @return void
     */
    public function setActionCode($ActionCode);

    /**
     * Get Status.
     *
     * @return varchar
     */
    public function getStatus();

    /**
     * Set status.
     *
     * @param varchar $Status
     * @return void
     */
    public function setStatus($Status);

    /**
     * Get UpdatedAt.
     *
     * @return varchar
     */
    public function getUpdatedAt();

    /**
     * Set UpdatedAt.
     *
     * @param varchar $UpdatedAt
     * @return void
     */
    public function setUpdatedAt($UpdatedAt);

    /**
     * Get CreatedAt.
     *
     * @return varchar
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt.
     *
     * @param varchar $createdAt
     * @return void
     */
    public function setCreatedAt($createdAt);
}
