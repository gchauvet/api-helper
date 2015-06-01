<?php
namespace DoYouBuzz\ApiHelper;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\Common\Storage\Memory;
use OAuth\Common\Storage\Session;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth1\Token\TokenInterface;
use OAuth\ServiceFactory;

class DoYouBuzzAPI
{

    /** @var  string */
    protected $apiKey;

    /** @var  string */
    protected $apiSecret;

    /** @var  DoYouBuzzService */
    protected $service;

    /** @var  TokenStorageInterface */
    protected $storage;

    /** @var  Uri */
    protected $currentUri;

    private $init = false;

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    protected function init($callbackUrl = null)
    {
        if (!$this->init) {
            if (!$this->storage) {
                $this->storage = new Session();
            }
            $uriFactory = new UriFactory();
            $this->currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);

            $credentials = new Credentials(
                $this->apiKey,
                $this->apiSecret,
                $callbackUrl ? $callbackUrl : $this->currentUri->getAbsoluteUri()
            );

            $serviceFactory = new ServiceFactory();
            $serviceFactory->registerService('DoYouBuzz', 'DoYouBuzz\ApiHelper\DoYouBuzzService');
            $this->service = $serviceFactory->createService('DoYouBuzz', $credentials, $this->storage);

            $this->init = true;
        }
    }

    /**u
     * @param bool $redirect Will return URL if false, will make the redir if true
     * @param null $callbackUrl By default the user will return to actual URI. You can override this.
     * @return \OAuth\Common\Token\TokenInterface|TokenInterface|string
     */
    public function connect($redirect = false, $callbackUrl = null)
    {
        $this->init($callbackUrl);
        if (!empty($_GET['oauth_token'])) {
            /** @var TokenInterface $token */
            $token = $this->storage->retrieveAccessToken(DoYouBuzzService::SERVICE_NAME . 'Service');
            // This was a callback request from DoYouBuzz, get the token
            return $this->service->requestAccessToken(
                $_GET['oauth_token'],
                $_GET['oauth_verifier'],
                $token->getRequestTokenSecret()
            );
            /*
            // Send a request now that we have access token
            $result = json_decode($this->service->request('/user'));
            echo 'result: <pre>' . print_r($result, true) . '</pre>';*/
        } else {
            $token = $this->service->requestRequestToken();
            $url = $this->service->getAuthorizationUri(
                array(
                    'oauth_token' => $token->getRequestToken(),
                    'oauth_callback' => $callbackUrl
                )
            );
            if ($redirect) {
                header('Location: ' . $url);
            } else {
                return $url;
            }
        }
    }

    public function isConnected()
    {
        return $this->storage->hasAccessToken(DoYouBuzzService::SERVICE_NAME . 'Service');
    }

    public function setAccessToken($accessToken)
    {
        $this->storage = new Memory();
        $this->storage->storeAccessToken(DoYouBuzzService::SERVICE_NAME, $accessToken);
    }

    protected function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $this->init();
        if (!$this->storage || !$this->storage->hasAccessToken(DoYouBuzzService::SERVICE_NAME)) {
            throw new ApiException('No Access Token defined, use setAccessToken or use connect before calling this method');
        }

        // Add format :
        if (strpos($path, '?') === false) {
            $path .= '?';
        } else {
            $path .= '&';
        }
        $path .= 'format=json';

        return json_decode($this->service->request($path, $method, $body, $extraHeaders));
    }

    public function getUserData()
    {
        return $this->request('/user');
    }

    public function getCvData($cvId)
    {
        return $this->request(sprintf('/cv/%s', $cvId));
    }

    public function getEmploymentPreferences()
    {
        return $this->request('/employmentpreferences');
    }

    public function getStatistics()
    {
        return $this->request('/user/stats');
    }

    public function getDisplayOptions($cvId)
    {
        return $this->request(sprintf('/cv/%s/display/web', $cvId));
    }



}