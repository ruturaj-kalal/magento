<?php
namespace Firework\Firework\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Integration\Model\ResourceModel\Integration\Collection;

class Integration implements ArrayInterface
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @param Collection $collection
     */
    public function __construct(
        Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * Get Integration Collection Datas
     *
     * @return array
     */
    public function toOptionArray()
    {
        $integrationDatas = $this->collection->getData();
        $integration = [];
        foreach ($integrationDatas as $integrationData) {
            $integration[] = [
                'value' => $integrationData['consumer_id'],
                'label' => $integrationData['name'],
            ];
        }

        return $integration;
    }
}
