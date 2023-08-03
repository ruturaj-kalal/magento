<?php
namespace Firework\Firework\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Firework\Firework\Model\Integration;

class IntegrationData implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var Integration
     */
    public $integration;

    /**
     * Module Data Setup Construct
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Integration $integration
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Integration $integration
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->integration = $integration;
    }

    /**
     * Create New Integration
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $this->integration->getIntegration();
        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get Aliases
     *
     * @return void
     */
    public function getAliases()
    {
        return [];
    }
    
    /**
     * Get Dependencies
     *
     * @return void
     */
    public static function getDependencies()
    {
        return [];
    }
}
