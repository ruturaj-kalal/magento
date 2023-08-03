<?php
namespace Firework\Firework\Model\ResourceModel\Webhook;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init('Firework\Firework\Model\Webhook', 'Firework\Firework\Model\ResourceModel\Webhook');
    }
}
