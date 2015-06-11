<?php
namespace DoYouBuzz\ApiHelper;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Http\Uri\UriFactory;
use OAuth\Common\Storage\Session;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\OAuth1\Token\TokenInterface;
use OAuth\ServiceFactory;

/**
 * Class DoYouBuzzAPI. Helper to call DoYouBuzz OAuth API
 *
 * @package DoYouBuzz\ApiHelper
 */
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

    /**
     * @return string
     */
    protected function getServiceName()
    {
        return DoYouBuzzService::SERVICE_NAME . 'Service';
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
            $serviceFactory->setHttpClient(new CurlClient());
            $serviceFactory->registerService('DoYouBuzz', 'DoYouBuzz\ApiHelper\DoYouBuzzService');
            $this->service = $serviceFactory->createService('DoYouBuzz', $credentials, $this->storage);

            $this->init = true;
        }
    }

    /**
     * Connect user with Oauth Dance :
     * - First call, will redirect (or return the URL) to request Token
     * - Second call, must be done on callback page, will get access token.
     * The second call return an array with AccessToken and AccessTokenSecret,
     * you may store it for future use.
     *
     * @param bool $redirect Will return URL if false, will make the redir if true
     * @param null $callbackUrl By default the user will return to actual URI. You can override this.
     * @return array|string|false
     */
    public function connect($redirect = false, $callbackUrl = null)
    {
        $this->init($callbackUrl);
        if (!empty($_GET['oauth_token'])) {
            /** @var TokenInterface $token */
            $token = $this->storage->retrieveAccessToken($this->getServiceName());
            // This was a callback request from DoYouBuzz, get the token
            $t = $this->service->requestAccessToken(
                $_GET['oauth_token'],
                $_GET['oauth_verifier'],
                $token->getRequestTokenSecret()
            );
            if ($t) {
                return array($t->getAccessToken(), $t->getAccessTokenSecret());
            } else {
                return false;
            }
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

    /**
     * Return if the user have a valid access token
     *
     * @return bool
     */
    public function isConnected()
    {
        $this->init();
        return $this->storage->hasAccessToken($this->getServiceName());
    }

    /**
     * Remove all stored token and authorization states
     */
    public function clearAll()
    {
        $this->storage->clearAllAuthorizationStates();
        $this->storage->clearAllTokens();
    }

    /**
     * Will use your own token (you may have stored in DB)
     *
     * @param $accessToken
     * @param $secret
     */
    public function setAccessToken($accessToken, $secret)
    {
        $this->init();

        $token = new StdOAuth1Token();
        $token->setAccessToken($accessToken);
        $token->setAccessTokenSecret($secret);
        $this->storage->storeAccessToken($this->getServiceName(), $token);
    }

    /**
     * Get the returned access token
     *
     * @return string|false
     */
    public function getAccessToken()
    {
        $this->init();
        if ($this->isConnected()) {
            return $this->storage->retrieveAccessToken($this->getServiceName())->getAccessToken();
        } else {
            return false;
        }
    }

    /**
     * @param $path
     * @param string $method
     * @param null $body
     * @param array $extraHeaders
     * @return \stdClass
     * @throws ApiException
     */
    protected function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $this->init();
        if (!$this->storage || !$this->storage->hasAccessToken($this->getServiceName())) {
            throw new ApiException('No Access Token defined, use setAccessToken or use connect before calling this method');
        }

        // Add format :
        strpos($path, '?') === false ? $path .= '?' : $path .= '&';
        $path .= 'format=json';

        return json_decode($this->service->request($path, $method, $body, $extraHeaders));
    }

    /**
     * Get user data
     * @return \stdClass
     * @throws ApiException
     */
    public function getUser()
    {
        $r = $this->request('/user');
        if (isset($r->user)) {
            return $r->user;
        }
        return false;
    }

    /**
     * Return the main CV of the user
     *
     * @return \stdClass
     */
    public function getMainCv()
    {
        $user = $this->getUser();
        foreach ($user->resumes->resume as $resume) {
            if ($resume->main) {
                return $this->getCv($resume->id);
            }
        }
    }

    /**
     * Get data for a CV owned by the user
     *
     * @param $cvId int
     * @return \stdClass
     * @throws ApiException
     */
    public function getCv($cvId)
    {
        $r = $this->request(sprintf('/cv/%s', $cvId));
        if (isset($r->resume)) {
            return $r->resume;
        }
        return false;
    }

    /**
     * Get employment preferences for the user
     *
     * @return \stdClass
     * @throws ApiException
     */
    public function getEmploymentPreferences()
    {
        $r = $this->request('/employmentpreferences');
        if (isset($r->employmentPreferences)) {
            return $r->employmentPreferences;
        }
        return false;
    }

    /**
     * Get statistics for the user
     *
     * @return \stdClass
     * @throws ApiException
     */
    public function getStatistics()
    {
        return $this->request('/user/stats');
    }

    /**
     * Return the display configuration for one view
     *
     * @param $cvId int
     * @param $type string Can be web|mobile|print
     * @return \stdClass
     * @throws ApiException
     */
    public function getDisplayOptions($cvId, $type)
    {
        return $this->request(sprintf('/cv/%s/display/%s', $cvId, $type));
    }

}