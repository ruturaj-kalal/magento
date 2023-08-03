<?php
namespace Firework\Firework\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\OauthService;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\Oauth\Token as OauthTokenModel;
use Firework\Firework\Helper\Data as FireworkHelper;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Check or Cerate new Integration
 */
class Integration extends AbstractModel
{
    public const INTEGRATION_NAME = 'FireWork';
    public const INTEGRATION_EMAIL = 'infofirework@gmail.com';

    /**
     * @var IntegrationFactory
     */
    protected $integrationFactory;

    /**
     * @var OauthService
     */
    protected $oauthService;
    
    /**
     * @var AuthorizationService
     */
    protected $authorizationService;

    /**
     * @var OauthTokenModel
     */
    protected $token;

    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param IntegrationFactory $integrationFactory
     * @param OauthService $oauthService
     * @param AuthorizationService $authorizationService
     * @param OauthTokenModel $token
     * @param FireworkHelper $fireworkHelper
     * @param WriterInterface $configWriter
     * @param LoggerInterface $logger
     */
    public function __construct(
        IntegrationFactory $integrationFactory,
        OauthService $oauthService,
        AuthorizationService $authorizationService,
        OauthTokenModel $token,
        FireworkHelper $fireworkHelper,
        WriterInterface $configWriter,
        LoggerInterface $logger
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->oauthService = $oauthService;
        $this->authorizationService = $authorizationService;
        $this->token = $token;
        $this->fireworkHelper = $fireworkHelper;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
    }

    /**
     * Check Token Exit or not
     *
     * @return string
     */
    public function getIntegration()
    {
        $consumerId = $this->fireworkHelper->getIntegrationToken();
        if (($consumerId != null) && !empty($consumerId)) {
            $accessToken = $this->oauthService->getAccessToken($consumerId);
            if ($accessToken) {
                return $accessToken->getData('token');
            }
        }

        return $this->generateIntegration();
    }

    /**
     * Create New Token and Save in DB
     *
     * @return string
     */
    public function generateIntegration()
    {
        $name = self::INTEGRATION_NAME;
        $email = self::INTEGRATION_EMAIL;

        $integrationExists = $this->integrationFactory->create()->load($name, 'name')->getData();
        if (empty($integrationExists)) {
            $integrationData = [
                'name' => $name,
                'email' => $email,
                'status' => '1',
                'setup_type' => '0'
            ];
            try {
                $integrationFactory = $this->integrationFactory->create();
                $integration = $integrationFactory->setData($integrationData);
                $integration->save();
                $integrationId = $integration->getId();
                $consumerName = $integrationId;
                
                // Code to create consumer
                $consumer = $this->oauthService->createConsumer(['name' => $consumerName]);
                $consumerId = $consumer->getId();
                $integration->setConsumerId($consumer->getId());
                $integration->save();
                
                // Code to grant permission
                $this->authorizationService->grantAllPermissions($integrationId);
                
                // Code to Activate and Authorize
                $uri = $this->token->createVerifierToken($consumerId);
                $this->token->setType('access');
                $this->token->save();

                try {
                    if ($this->token->getType() != OauthTokenModel::TYPE_ACCESS) {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
                $this->configWriter->save(
                    FireworkHelper::INTEGRATION_TOKEN,
                    $consumerId,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );
                $this->fireworkHelper->flushCache();
                return $this->token->getToken();
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
