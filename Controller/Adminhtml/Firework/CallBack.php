<?php
namespace Firework\Firework\Controller\Adminhtml\Firework;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Firework\Firework\Helper\Data as FireworkHelper;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;

class CallBack extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    private const ACCESS_TOKEN = 'firework/general/access_token';
    private const REFRESH_TOKEN = 'firework/general/refresh_token';
    private const BUSSINESS_STORE_ID = 'firework/general/business_store_id';
    private const THIRD_API_URL = 'https://fireworktv.com/oauth/token';

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var FireworkHelper
     */
    protected $fireworkHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * Construct function called
     *
     * @param Context $context
     * @param Data $jsonHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param FireworkHelper $fireworkHelper
     * @param RedirectFactory $resultRedirectFactory
     * @param LoggerInterface $logger
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        Data $jsonHelper,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        FireworkHelper $fireworkHelper,
        RedirectFactory $resultRedirectFactory,
        LoggerInterface $logger,
        Curl $curl
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->fireworkHelper = $fireworkHelper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->logger = $logger;
        $this->curl = $curl;
        parent::__construct($context);
    }

    /**
     * Callback in API URL
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $data = $this->getRequest()->getParams();
            $storeInfo = $this->fireworkHelper->checkExitsStoreandWebite($data);

            /**
             * Check If click on cancel button
             * return configuration
             */
            if (isset($data['error'])) {
                $resultRedirect->setUrl($this->fireworkHelper->getBackendUrl(
                    $storeInfo['scope_store'],
                    $storeInfo['store_id']
                ));
                return $resultRedirect;
            }

            $resultObjts = json_decode($this->fireworkHelper->getFirstApiResponse());
            $redirectUris = $resultObjts->redirect_uris[0];
            $clientId = $resultObjts->client_id;
            $clientSecret = $resultObjts->client_secret;
            $authorizationCode = $data['code'];

            if (!isset($data['business_id'])) {
                $resultRedirect->setUrl($this->fireworkHelper->getBackendUrl(
                    $storeInfo['scope_store'],
                    $storeInfo['store_id']
                ));
                return $resultRedirect;
            }

            $businessId = $data['business_id'];
            $urlParam = "?grant_type=authorization_code&redirect_uri=".
            $redirectUris."&client_id=$clientId&client_secret=$clientSecret&code=$authorizationCode";
            $thirdApiUrl = self::THIRD_API_URL . $urlParam;
            
            // API call
            $header = [
                "Content-Type" => "application/json"
            ];
            $this->curl->setHeaders($header);
            $this->curl->post($thirdApiUrl, []);
            $body = $this->curl->getBody();
            $statusCode = $this->curl->getStatus();

            /**
             * Get auth_code and if it's expire or not check and based on that operation perform,
             * bussiness_id save or create based on data
             */
            if ($statusCode == 200) {
                $resultObjts = json_decode($body);
                $this->configWriter->save(
                    self::ACCESS_TOKEN,
                    $resultObjts->access_token,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );
                $this->configWriter->save(
                    self::REFRESH_TOKEN,
                    $resultObjts->refresh_token,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );
                $this->configWriter->save(
                    FireworkHelper::BUSINESS_ID,
                    $data['business_id'],
                    $storeInfo['scope'],
                    $storeInfo['store_id']
                );

                $this->fireworkHelper->flushCache();
                
                /// Start Code to check Bussiness Exist or not
                $businessResponse = $this->fireworkHelper->checkBusinessExists($businessId, $resultObjts->access_token);
                /// End Code to check Bussiness Exist or not

                $checkBusinessExists = json_decode($businessResponse);
                
                /**
                 * We have checked response value and if its "null" then we create a bussiness with call API.
                 * And if exist, we store business ID into Mageto database
                 */
                if ($checkBusinessExists->business_stores == null) {
                    $responseData = $this->fireworkHelper->createBusinessStore($resultObjts->access_token, $businessId);
                    $resultObjts = json_decode($responseData);
                    $this->configWriter->save(
                        self::BUSSINESS_STORE_ID,
                        $resultObjts->data->createBusinessStore->id,
                        $storeInfo['scope'],
                        $storeInfo['store_id']
                    );
                    $this->fireworkHelper->flushCache();
                } else {
                    $isUidExist = false;
                    foreach ($checkBusinessExists->business_stores as $business_store) {
                        if ($business_store->uid == $this->fireworkHelper->getStoreBaseUrl()) {
                            $isUidExist = true;
                            break;
                        }
                    }
                    if (!$isUidExist) {
                        $responseData = $this->fireworkHelper->createBusinessStore(
                            $resultObjts->access_token,
                            $businessId
                        );
                        $resultObjts = json_decode($responseData);
                        $this->configWriter->save(
                            self::BUSSINESS_STORE_ID,
                            $resultObjts->data->createBusinessStore->id,
                            $storeInfo['scope'],
                            $storeInfo['store_id']
                        );
                        $this->fireworkHelper->flushCache();
                    } else {
                        $this->configWriter->save(
                            self::BUSSINESS_STORE_ID,
                            $business_store->id,
                            $storeInfo['scope'],
                            $storeInfo['store_id']
                        );
                    }
                }
                $resultRedirect->setUrl($this->fireworkHelper->getBackendUrl(
                    $storeInfo['scope_store'],
                    $storeInfo['store_id']
                ));
                return $resultRedirect;
            } else {
                $this->fireworkHelper->getAccessFromRefreshToken();
            }

            $resultObjts = json_decode($body);
            if (isset($resultObjts->error)) {
                $this->logger->critical('Error: "' . $resultObjts->error . '" - Error Description: '.
                $resultObjts->error_description);
                $resultRedirect->setUrl($this->fireworkHelper->getBackendUrl(
                    $storeInfo['scope_store'],
                    $storeInfo['store_id']
                ));
                return $resultRedirect;
            }
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
