<?php
namespace Firework\Firework\Helper;

use Exception;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Currency;
use Magento\Store\Model\ScopeInterface;
use Magento\Integration\Model\OauthService;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Helper Data
 */
class Data extends AbstractHelper
{
    public const ACCESS_TOKEN = 'firework/general/access_token';
    public const BUSSINESS_STORE_ID = 'firework/general/business_store_id';
    public const BUSINESS_ID = 'firework/general/business_id';
    public const JSON_RESPONSE = 'firework/general/json_response';
    public const REFRESH_TOKEN = 'firework/general/refresh_token';
    public const STORE_NAME = 'general/store_information/name';
    public const OWNER_EMAIL = 'trans_email/ident_general/email';
    public const INTEGRATION_TOKEN = 'firework/firework/integration_token';
    public const TRACKING = 'firework/tracking/enable';
    public const PROVIDER = 'magento';
    public const ENDPOINT = 'https://fireworktv.com/graphiql';
    public const BUSAPI = 'https://fireworktv.com/api/bus';
    public const THIRD_API_URL = 'https://fireworktv.com/oauth/token';

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Pool
     */
    protected $cacheFrontendPool;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $backendUrl;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RedirectInterface
     */
    protected $redirect;
    
    /**
     * @var Http
     */
    protected $response;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Currency
     */
    protected $currency;

    /**
     * @var OauthService
     */
    protected $oauthService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $backendUrl
     * @param WriterInterface $configWriter
     * @param ManagerInterface $messageManager
     * @param Http $response
     * @param RedirectInterface $redirect
     * @param StoreManagerInterface $storeManager
     * @param Currency $currency
     * @param OauthService $oauthService
     * @param LoggerInterface $logger
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $backendUrl,
        WriterInterface $configWriter,
        ManagerInterface $messageManager,
        Http $response,
        RedirectInterface $redirect,
        StoreManagerInterface $storeManager,
        Currency $currency,
        OauthService $oauthService,
        LoggerInterface $logger,
        Curl $curl
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->scopeConfig = $scopeConfig;
        $this->backendUrl = $backendUrl;
        $this->configWriter = $configWriter;
        $this->messageManager = $messageManager;
        $this->redirect = $redirect;
        $this->response = $response;
        $this->storeManager = $storeManager;
        $this->currency = $currency;
        $this->oauthService = $oauthService;
        $this->logger = $logger;
        $this->curl = $curl;
        parent::__construct($context);
    }

    /**
     * Get Scope Id and Scope type
     *
     * @return array
     */
    public function getScopeId()
    {
        if ($this->_request->getParam(ScopeInterface::SCOPE_STORE)) {
            $scope = ScopeInterface::SCOPE_STORE;
            $storeId = $this->_request->getParam(ScopeInterface::SCOPE_STORE);
        } elseif ($website = $this->_request->getParam(ScopeInterface::SCOPE_WEBSITE)) {
            $scope = ScopeInterface::SCOPE_WEBSITE;
            $storeId = $this->_request->getParam(ScopeInterface::SCOPE_WEBSITE);
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $storeId = 0;
        }

        $storeInfos = [
            'scope' => $scope,
            'store_id' => $storeId
        ];

        return $storeInfos;
    }

    /**
     * Check exits Store and Website
     *
     * @param [type] $data
     * @return array
     */
    public function checkExitsStoreandWebite($data)
    {
        $scope = '';
        $storeId = '';

        if (array_key_exists("store", $data)) {
            $scope = ScopeInterface::SCOPE_STORES;
            $scopeStore = ScopeInterface::SCOPE_STORE;
            $storeId = $data['store'];
        } elseif (array_key_exists("website", $data)) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeStore = ScopeInterface::SCOPE_WEBSITE;
            $storeId = $data['website'];
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeStore = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $storeId = 0;
        }
        $storeInfos = [
            'scope' => $scope,
            'store_id' => $storeId,
            'scope_store' => $scopeStore
        ];

        return $storeInfos;
    }

    /**
     * Get Store And Website Data
     *
     * @param [type] $data
     * @return array
     */
    public function getStoreAndWebsite($data)
    {
        $scope = '';
        $storeId = '';

        if ($data['store_scope'] == ScopeInterface::SCOPE_STORE) {
            $scope = ScopeInterface::SCOPE_STORES;
            $scopeStore = ScopeInterface::SCOPE_STORE;
            $storeId = $data['store_id'];
        } elseif ($data['store_scope'] == ScopeInterface::SCOPE_WEBSITE) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeStore = ScopeInterface::SCOPE_WEBSITE;
            $storeId = $data['store_id'];
        } else {
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeStore = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $storeId = 0;
        }

        $storeInfos = [
            'scope' => $scope,
            'store_id' => $storeId,
            'scope_store' => $scopeStore
        ];

        return $storeInfos;
    }
    /**
     * Get Oauth User Token
     *
     * @return string
     */
    public function getIntegrationToken()
    {
        return $this->scopeConfig->getValue(
            self::INTEGRATION_TOKEN,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Magento Access Token
     *
     * @return string|null
     */
    public function getMagentoAccessToken()
    {
        $consumerId = $this->getIntegrationToken();
        $accessToken = $this->oauthService->getAccessToken($consumerId);
        if ($accessToken) {
            return $accessToken->getData('token');
        }
        
        return '';
    }
    
    /**
     * Get Access Token From DB And check Valid or Not
     *
     * @param [type] $scope
     * @param [type] $storeId
     * @return string
     */
    public function getAccessToken($scope = null, $storeId = null)
    {
        $storeInfo = $this->getScopeId();
        $accessToken = $this->scopeConfig->getValue(
            self::ACCESS_TOKEN,
            ($scope != null) ? $scope : ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        $bussinessStoreId = $this->getBussinessStoreId($storeInfo['scope'], $storeInfo['store_id']);
        $bussinessId = $this->getBussinessId($storeInfo['scope'], $storeInfo['store_id']);
        if (($accessToken != null) && !empty($accessToken) &&
            ($bussinessStoreId != null) && !empty($bussinessStoreId) &&
            ($bussinessId != null) && !empty($bussinessId)
            ) {
            if ($statusCode = $this->checkAccessTokenValid($accessToken)) {
                if ($statusCode == 200) {
                    return $accessToken;
                } else {
                    return $this->getAccessFromRefreshToken();
                }
            }
        } else {
            $this->flushCache();
            $this->messageManager->addWarning(
                __("Required data missing. Please click connect button")
            );
            return $this->redirect->redirect($this->response, 'adminhtml/system_config/edit/section/firework');
        }
    }

    /**
     * Get Access Token From DB
     *
     * @return string
     */
    public function getNewAccessToken()
    {
        return $this->scopeConfig->getValue(
            self::ACCESS_TOKEN,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
    /**
     * Get Refresh Token From DB
     *
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->scopeConfig->getValue(
            self::REFRESH_TOKEN,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Bussiness Store Id From DB
     *
     * @param [type] $scope
     * @param [type] $storeId
     * @return string
     */
    public function getBussinessStoreId($scope = null, $storeId = null)
    {
        if ($scope == ScopeInterface::SCOPE_STORE) {
            return $this->scopeConfig->getValue(
                self::BUSSINESS_STORE_ID,
                $scope,
                $storeId
            );
        } elseif ($scope == ScopeInterface::SCOPE_WEBSITE) {
            return $this->scopeConfig->getValue(
                self::BUSSINESS_STORE_ID,
                $scope,
                $storeId
            );
        } else {
            return $this->scopeConfig->getValue(
                self::BUSSINESS_STORE_ID,
                ($scope != null) ? $scope : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $storeId
            );
        }
    }

    /**
     * Get Oauth Bussiness Store Id From DB
     *
     * @param [type] $scope
     * @param [type] $storeId
     * @return string
     */
    public function getBussinessId($scope = null, $storeId = null)
    {
        if ($scope == ScopeInterface::SCOPE_STORE) {
            return $this->scopeConfig->getValue(
                self::BUSINESS_ID,
                $scope,
                $storeId
            );
        } elseif ($scope == ScopeInterface::SCOPE_WEBSITE) {
            return $this->scopeConfig->getValue(
                self::BUSINESS_ID,
                $scope,
                $storeId
            );
        } else {
            return $this->scopeConfig->getValue(
                self::BUSINESS_ID,
                ($scope != null) ? $scope : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $storeId
            );
        }
    }

    /**
     * Get Admin URL
     *
     * @param [type] $scope
     * @param [type] $storeId
     * @return string
     */
    public function getBackendUrl($scope, $storeId = null)
    {
        return $this->backendUrl->getRouteUrl(
            'firework/firework/dashboard',
            [
                $scope => $storeId,
                'key' => $this->backendUrl->getSecretKey('firework', 'firework', 'dashboard')
            ]
        );
    }

    /**
     * Get Admin URL
     *
     * @return string
     */
    public function getAdminUrl()
    {
        return $this->backendUrl->getRouteUrl('adminhtml');
    }
    /**
     * Get Call Back URL
     *
     * @param [type] $scope
     * @param [type] $storeId
     * @return string
     */
    public function getCallBackUrl($scope, $storeId = null)
    {
        return $this->backendUrl->getUrl(
            'firework/firework/callback',
            [$scope => $storeId]
        );
    }

    /**
     * Get First API Json Response
     *
     * @return string
     */
    public function getFirstApiResponse()
    {
        return $this->scopeConfig->getValue(
            self::JSON_RESPONSE,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Store Name
     *
     * @return void
     */
    public function getStoreName()
    {
        $storeName = $this->scopeConfig->getValue(
            self::STORE_NAME,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );

        return ($storeName != null) ? $storeName : "Magento 2 Store";
    }

    /**
     * Get Admin Email
     *
     * @return void
     */
    public function getOwnerEmail()
    {
        return $this->scopeConfig->getValue(
            self::OWNER_EMAIL,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Get Currency Code
     *
     * @return void
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get Store BaseUrl
     *
     * @return void
     */
    public function getStoreBaseUrl()
    {
        $storeUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $storeNewUrl = rtrim($storeUrl, '/');
        return $storeNewUrl;
    }

    /**
     * Get Access Token Using Refresh Token
     *
     * @return string
     */
    public function getAccessFromRefreshToken()
    {
        $this->flushCache();
        $newAccessToken = false;
        if ($this->getFirstApiResponse() == null) {
            return false;
        }
        $resultObjts = json_decode($this->getFirstApiResponse());
        $redirectUri = $resultObjts->redirect_uris[0];
        $clientId = $resultObjts->client_id;
        $refreshToken = $this->getRefreshToken();

        $urlParam = "?grant_type=refresh_token&client_id=".
        $clientId."&redirect_uri=$redirectUri&refresh_token=$refreshToken";
        $thirdApiUrl = self::THIRD_API_URL . $urlParam;
        $headers = [
            "Content-Type" => "application/json"
        ];
        
        $this->curl->setHeaders($headers);
        $this->curl->post($thirdApiUrl, []);

        // get response
        $body = $this->curl->getBody();
        $resultObjts = json_decode($body);
        if (!isset($resultObjts->error) && !empty($resultObjts->access_token) && !empty($resultObjts->refresh_token)) {
            $this->configWriter->save(
                self::ACCESS_TOKEN,
                $resultObjts->access_token,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
            $newAccessToken = $resultObjts->access_token;
            $this->configWriter->save(
                self::REFRESH_TOKEN,
                $resultObjts->refresh_token,
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        } else {
            $this->messageManager->addError(
                __("Firework Service is not available.")
            );
        }

        return $newAccessToken;
    }

    /**
     * Check Access Token Valid or not
     *
     * @param [type] $accessToken
     * @return string
     */
    public function checkAccessTokenValid($accessToken)
    {
        $headers = [
            "Authorization" => "Bearer " .$accessToken
        ];

        $busApi = self::BUSAPI;
        $this->curl->setHeaders($headers);
        $this->curl->get($busApi);
        $statusCode = $this->curl->getStatus();

        return $statusCode;
    }

    /**
     * Create Business Store
     *
     * @param [type] $accessToken
     * @param [type] $businessId
     * @return void
     */
    public function createBusinessStore($accessToken, $businessId)
    {
        $endpoint = self::ENDPOINT;

        $business_id = $businessId;
        $currency    = $this->getCurrencyCode();
        $siteTitle   = $this->getStoreName();
        $provider    = self::PROVIDER;
        $siteurl     = $this->getStoreBaseUrl();
        $magentoAccessToken = $this->getMagentoAccessToken();
        $accessToken = $accessToken;

        $query ='mutation {
            createBusinessStore(createBusinessStoreInput:{businessId: "'.
                 $business_id .'", currency: "'. $currency .'", name: "'. $siteTitle .'", provider: "'.$provider.'", uid: "'. $siteurl .'", accessToken: "'. $magentoAccessToken .'", refreshToken: "'.$magentoAccessToken .'", metadata: '.
            '"{\"product_delete_webhook_secret\": \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\", '.
            '\"product_update_webhook_secret\": \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\", '.
            '\"product_restore_webhook_secret\": \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\"}"}) {
            ... on BusinessStore {
                    id
                    name
                    provider
                    currency
                    url
                    accessToken
                    refreshToken
                }
                ... on AnyError {
                    message
                }
            }
        }';

        $data = ['query' => $query];
        $data = http_build_query($data);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $accessToken
        ];
        
        $options = [
            'http' => [
                'header'  => $headers,
                'method'  => 'POST',
                'content' => $data
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($endpoint, false, $context);

        if ($result === false) {
            $error = error_get_last();
            return "HTTP request failed. Error was: " . $error['message'];
        }

        return $result;
    }

    /**
     * Update Business Store
     *
     * @param [type] $accessToken
     * @return void
     */
    public function updateBusinessStore($accessToken)
    {
        $endpoint = self::ENDPOINT;
        $storeId = $this->getBussinessStoreId();
        $business_id = $this->getBussinessId();
        $currency    = $this->getCurrencyCode();
        $accessToken = $accessToken;

        $query ='mutation {
            updateBusinessStore( storeId: "'. $storeId .'", updateBusinessStoreInput:{ businessId: "'.
            $business_id .'", currency: "'. $currency .'", metadata: '.
            '"{ \"product_delete_webhook_secret\" : \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\", '.
            '\"product_restore_webhook_secret\" : \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\", '.
            '\"product_update_webhook_secret\" : \"g;fM*J}^mf1DvFYZC7 0 jZdgk~(Fx3<mfWm)I}5p@e{$^?WT$\" }" }) {
            ... on BusinessStore {
                    id
                    name
                    provider
                    currency
                    url
                    accessToken
                    uid
                    refreshToken
                    business {
                      id
                    } 
                }
                ... on AnyError {
                    message
                }
            }
        }';

        $data = ['query' => $query];
        $data = http_build_query($data);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $accessToken
        ];
        
        $options = [
            'http' => [
                'header'  => $headers,
                'method'  => 'POST',
                'content' => $data
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($endpoint, false, $context);

        if ($result === FALSE) {
            $error = error_get_last();
            return "HTTP request failed. Error was: " . $error['message'];
        }
    }

    /**
     * Check Business Account Exits
     *
     * @param [type] $businessId
     * @param [type] $accessToken
     * @return string
     */
    public function checkBusinessExists($businessId, $accessToken)
    {
        $url = self::BUSAPI . "/$businessId/business_stores?page_size=10";
        
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $accessToken
        ];
        
        $this->curl->setHeaders($headers);
        $this->curl->get($url);
        $response = $this->curl->getBody();
        $statusCode = $this->curl->getStatus();

        if ($statusCode == 200) {
            return $response;
        } else {
            $this->logger->critical('Error-Code: ' . $statusCode);
        }
    }

    /**
     * System Configuration Cache Flush
     *
     * @return void
     */
    public function flushCache()
    {
        $_types = [
            'config'
        ];
    
        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    /**
     * Check tracking is enabled.
     *
     * @return bool
     */
    public function isTrackingEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            self::TRACKING,
            ScopeInterface::SCOPE_STORE
        );
    }
}
