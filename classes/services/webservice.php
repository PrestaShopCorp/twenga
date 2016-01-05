<?php
/**
 * Copyright (c) 2015 Twenga 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions: 
 * 
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software. 
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE 
 * OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * @author    Twenga
 * @copyright 2016 Twenga
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

/**
 * Class Webservice
 * @package Twenga\Services
 */
class Twenga_Services_Webservice
{
    /**
     * Webservice domain
     * @var string
     */
    private $webserviceDomain;

    /**
     * Twenga-Solutions product id
     * @var int
     */
    private $productId;

    /**
     * Twenga Geozone id
     * @var int
     */
    private $geozoneId;

    /**
     * API authentication token
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $lastHttpCode;

    /**
     * Heders of the last call
     * @var array
     */
    private $lastHeaders = array();

    /**
     * curl headers
     * @var array
     */
    private $headers = array(
        'Accept' => 'application/json'
    );

    /**
     * Default options used for the curl requests
     * @var array
     */
    private $curlOptions = array(
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT =>
            'Mozilla/4.0(compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
        CURLOPT_REFERER => '',
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_HEADER => true
    );

    /**
     * Webservice constructor.
     * @param string $webserviceDomain
     * @param int $productId
     * @param int $geozoneId
     */
    public function __construct($webserviceDomain, $productId, $geozoneId)
    {
        $this->webserviceDomain = $webserviceDomain;
        $this->productId = $productId;
        $this->geozoneId = $geozoneId;
    }

    /**
     * Call webservice GET /module/signup
     * @return mixed
     */
    public function getFormSignUp()
    {
        $url = $this->buildUrl(
            '/module/signup',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => $this->geozoneId,
            )
        );
        return $this->callGET($url);
    }

    /**
     * Call webservice GET /module/login
     * @return mixed
     */
    public function getFormLogin()
    {
        $url = $this->buildUrl(
            '/module/login',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => $this->geozoneId,
            )
        );
        return $this->callGET($url);
    }

    /**
     * Call webservice POST /module/signu
     * @param array $data
     * @return mixed
     */
    public function postSignUp(array $data)
    {
        $url = $this->buildUrl(
            '/module/signup',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => $this->geozoneId
            )
        );

        $response = $this->callPOST($url, $data);

        if (isset($response['auth']) && isset($response['auth']['token'])) {
            $this->token = $response['auth']['token'];
        }
        return $response;
    }

    /**
     * Call webservice POST /module/lostpassword
     * @param string $email
     * @return array
     */
    public function postLostPassword($email)
    {
        $url = $this->buildUrl(
            '/module/lostpassword',
            array(
                'PRODUCT_ID' => $this->productId,
                'GEOZONE_CODE' => $this->geozoneId
            )
        );
        return $this->callPOST(
            $url,
            array(
                'EMAIL' => $email
            )
        );
    }

    /**
     * Authenticate
     * @param string $extranetSiteId
     * @param string $apiKey
     * @return mixed
     */
    public function authenticate($extranetSiteId, $apiKey)
    {
        $url = $this->buildUrl('/authenticate');

        $this->curlOptions[CURLOPT_USERPWD] = $extranetSiteId . ':' . $apiKey;

        $response = $this->callGET($url);

        if (isset($response['auth']) && isset($response['auth']['token'])) {
            $this->token = $response['auth']['token'];
        }
        return $response;
    }

    /**
     * Call webservice POST /authenticate/email
     * @param array $data
     * @return array
     */
    public function authenticateEmail(array $data)
    {
        $url = $this->buildUrl('/authenticate/email');
        $response = $this->callPOST($url, $data);

        if (isset($response['auth']) && isset($response['auth']['token'])) {
            $this->token = $response['auth']['token'];
        }
        return $response;
    }

    /**
     * Get tracking script
     * @return mixed
     */
    public function getTrackingScript()
    {
        $url = $this->buildUrl('/tracker', array('token' => $this->token));
        $response = $this->callGET($url);
        return $response;
    }

    /**
     * Get current account information
     * @return mixed
     */
    public function getAccountInfo()
    {
        $url = $this->buildUrl('/account', array('token' => $this->token));
        $response = $this->callGET($url);
        return $response;
    }

    /**
     * Get current account information
     * @return array
     */
    public function getProduct()
    {
        $url = $this->buildUrl(
            '/product',
            array(
                'token' => $this->token,
                'PRODUCT_ID' => $this->productId
            )
        );
        $response = $this->callGET($url);

        if (
            isset($response['products'])
            && isset($response['products'][0])
            && $response['products'][0]['PRODUCT_ID'] == $this->productId
        ) {
            return $response['products'][0];
        }
        return array();
    }

    /**
     * Build url using path and parameters
     * @param string $path
     * @param array $parameters
     * @return string
     */
    private function buildUrl($path, array $parameters = array())
    {
        $url = $this->webserviceDomain . $path;
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        return $url;
    }

    /**
     * Execute curl call using GET
     * @param $url
     * @return mixed
     * @throws Twenga_Exception
     */
    private function callGET($url)
    {
        $resource = $this->getCurlResource($url);
        return $this->curlCall($resource);
    }

    /**
     * Execute curl call using POST with given data
     * @param string $url
     * @param array $data
     * @return array
     * @throws Twenga_Exception
     */
    private function callPOST($url, array $data)
    {
        $resource = $this->getCurlResource($url);
        curl_setopt_array(
            $resource,
            array(
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $data
            )
        );

        return $this->curlCall($resource);
    }

    /**
     * Get curl resource
     * @param string $url
     * @return resource
     */
    private function getCurlResource($url)
    {
        $curlResource = \curl_init($url);
        \curl_setopt_array($curlResource, $this->curlOptions);
        return $curlResource;
    }

    /**
     * Execute curl call
     *
     * @param $resource
     * @return array
     * @throws Twenga_Exception
     */
    private function curlCall($resource)
    {
        \curl_setopt($resource, CURLOPT_HTTPHEADER, $this->getFormattedHeaders());

        $response = \curl_exec($resource);
        $curlInfo = \curl_getinfo($resource);

        $headers = \Tools::substr($response, 0, $curlInfo['header_size']);
        $response = trim(\Tools::substr($response, $curlInfo['header_size']-1));

        $this->parseHeader($headers);
        $this->lastHttpCode = $curlInfo['http_code'];

        $json = \Tools::jsonDecode($response, true);

        if (false === $json || !is_array($json)) {
            throw new Twenga_Exception(
                'Can\'t decode json. An error may occurred with http code ' . $curlInfo['http_code']
            );
        }

        return $json;
    }

    /**
     * Parse header string
     * @param string $header
     * @return $this
     */
    protected function parseHeader($header)
    {
        $this->lastHeaders = array();
        $headerLines = explode("\n", $header);
        foreach ($headerLines as $line) {
            $headerParts = explode(':', $line, 2);
            if (!isset($headerParts[1])) {
                continue;
            }

            $value = trim($headerParts[1]);
            $key = $headerParts[0];

            if (isset($this->lastHeaders[$key])) {
                if (!is_array($this->lastHeaders[$key])) {
                    $this->lastHeaders[$key] = array($this->lastHeaders[$key]);
                }
                $this->lastHeaders[$key][] = $value;
            } else {
                $this->lastHeaders[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Get formatted headers for curl
     * @return array
     */
    private function getFormattedHeaders()
    {
        $output = array();
        foreach ($this->headers as $key => $value) {
            $output[] = $key . ': ' . $value;
        }
        return $output;
    }

    /**
     * Add curl header
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = (string)$value;
        return $this;
    }

    /**
     * Get last http code
     * @return int
     */
    public function getLastHttpCode()
    {
        return $this->lastHttpCode;
    }

    /**
     * Get last headers
     * @return array
     */
    public function getLastHeaders()
    {
        return $this->lastHeaders;
    }

    /**
     * Is last http code like 2xx
     * @return bool
     */
    public function isLastHttpCode2xx()
    {
        return 2 === (int)floor($this->lastHttpCode / 100);
    }

    /**
     * Is last http code like 3xx
     * @return bool
     */
    public function isLastHttpCode3xx()
    {
        return 3 === (int)floor($this->lastHttpCode / 100);
    }

    /**
     * Is last http code like 4xx
     * @return bool
     */
    public function isLastHttpCode4xx()
    {
        return 4 === (int)floor($this->lastHttpCode / 100);
    }

    /**
     * Is last http code like 5xx
     * @return bool
     */
    public function isLastHttpCode5xx()
    {
        return 5 === (int)floor($this->lastHttpCode / 100);
    }

    /**
     * Set token
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
}
