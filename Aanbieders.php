<?php
/*
Plugin Name: Aanbieders Api Client
Plugin URI: https://github.com/econtract/ApiClient
Description: Aanbieders API Client Plugin for Wordpress.
Version: 1.1.8
Author: Imran Zahoor
Author URI: http://imranzahoor.wordpress.com/
License: A "Slug" license name e.g. GPL2
*/

namespace AnbApiClient;

use Exception;

/**
 * Aanbieders API Class
 * This class is a wrapper for making a curl based request to the api of aanbieders.be
 *
 * Contact for support:
 * Aanbieders API Support <api_support@aanbieders.be>
 */
class Aanbieders
{
    /*
     * To apply for a API key and secret please contact
     *
     * api_support@econtract.be
     */
    /** @var string */
    private $key;

    /** @var string */
    private $secret;

    /** @var string */
    private $host;

    /** @var string */
    private $outputType = 'json';

    /**
     * @var string
     */
    private $language;

    /**
     * Constructor: Initializes the instance
     *
     * @param array $config API configuration
     * @throws Exception
     */
    public function __construct($config)
    {
        if (empty($config['key'])) {
            throw new Exception('Invalid key');
        }

        if (empty($config['secret'])) {
            throw new Exception('Invalid secret');
        }

        $this->key      = $config['key'];
        $this->secret   = $config['secret'];
        $this->host     = isset($config['host']) ? $config['host'] : 'https://api.econtract.be';
        $this->language = isset($config['language']) ? $config['language'] : 'nl';
        $this->abcid    = uniqid();
    }

    /**
     * Compare products
     *
     * @param array $params the required params to make a comparing
     * @return mixed
     */
    public function compare($params)
    {
        $url = $this->host . "/comparison.json";

        return $this->doCall($url, $params);
    }

    /**
     * Get previous comparison result by comparison ID
     *
     * @params int $comparisonId id to fetch previous compare result
     * @params array $params
     * @return mixed
     */
    public function previousCompare($comparisonId, $params = [])
    {
        $url = $this->host . "/comparison/" . $comparisonId . ".json";

        return $this->doCall($url, $params);
    }

    /**
     * Get suppliers
     *
     * @param array $params array with parameters for suppliers
     * @return mixed
     */
    public function getSuppliers($params = [])
    {
        $url = $this->host . '/suppliers.json';

        return $this->doCall($url, $params);
    }

    /**
     * Get a supplier by slug or ID
     *
     * @param int|string $slugOrId
     * @param array      $params
     * @return mixed
     */
    public function getSupplier($slugOrId, $params = [])
    {
        $url = $this->host . '/suppliers/' . $slugOrId . '.json';

        return $this->doCall($url, $params);
    }

    /**
     * Get 1 or more products
     *
     * @param array     $params     Array of parameters
     * @param int|array $productIds 1 product ID/slug or array of product IDs/slugs
     * @return mixed
     */
    public function getProducts($params, $productIds = [])
    {
        $productIds = (array)$productIds;
        if (count($productIds) === 1) {
            return $this->getProduct($productIds[0], $params);
        }

        $url = $this->host . "/products.json";
        if (!empty($productIds)) {
            $params['productid'] = $productIds;
        }

        return $this->doCall($url, $params);
    }

    /**
     * Get a product by slug or ID
     *
     * @param int|string $slugOrId
     * @param array      $params
     * @return mixed
     */
    public function getProduct($slugOrId, $params = [])
    {
        $url = $this->host . "/products/" . $slugOrId . ".json";

        return $this->doCall($url, $params);
    }

    /**
     * Get last updated time
     *
     * @param array $params
     * @return mixed
     */
    public function getProductsLastUpdated($params = [])
    {
        $url = $this->host . '/products/last_updated.json';

        return $this->doCall($url, $params);
    }

    /**
     * Check availability for a product by postal code
     *
     * @param int        $productId
     * @param string|int $postalCode
     * @param array      $params
     * @return mixed
     */
    public function getProductAvailability($productId, $postalCode, $params = [])
    {
        $params['zip'] = $postalCode;

        $url = $this->host . '/products/is_available_at/' . $productId . '.json';

        return $this->doCall($url, $params);
    }

    /**
     * Get price breakdown structure for a product
     *
     * @param int   $productId
     * @param array $params
     * @return mixed
     */
    public function getProductPbs($productId, $params = [])
    {
        $url = $this->host . '/products/pbs/' . $productId . '.json';

        return $this->doCall($url, $params);
    }

    /**
     * Get reviews
     *
     * @param array $params array with parameters for reviews
     * @return mixed
     */
    public function getReviews($params = [])
    {
        $url = $this->host . '/reviews.json';

        return $this->doCall($url, $params);
    }

    /**
     * Get sales agents
     *
     * @param array $params
     * @return mixed
     */
    public function getSalesAgents($params = [])
    {
        $url = $this->host . '/sales_agent.json';

        return $this->doCall($url, $params);
    }

    /**
     * Do a call to given url with parameters
     *
     * @param string $url        Url of the resource
     * @param array  $parameters Required and optional parameters for the resource
     * @param string $method     The HTTP-method, default is POST
     * @return mixed with request response
     */
    protected function doCall($url, $parameters = [], $method = 'GET')
    {
        $parameters += [
            'lang' => $this->language,
        ];

        $parameters['key']  = $this->key;
        $parameters['time'] = time();

        // create a nonce
        $parameters['nonce'] = md5(uniqid());

        // create the api key
        $parameters['ip']     = static::getIp();
        $parameters['apikey'] = hash_hmac('sha1', $this->key, $this->secret . $parameters['time'] . $parameters['nonce']);

        // add the unique id
        $parameters['abcid'] = $this->abcid;

        $requestParams = http_build_query($parameters);

        // curl handler
        $curlHandle = curl_init();

        // set curl options
        // return the result so we can catch it as a variable
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        if (strtolower($method) === 'POST') {
            curl_setopt($curlHandle, CURLOPT_POST, true);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestParams);
        } else {
            // do the request
            $url = $url . '?' . $requestParams;
        }

        // do request
        curl_setopt($curlHandle, CURLOPT_URL, $url);

        $response = curl_exec($curlHandle);
        // close
        curl_close($curlHandle);
        switch ($this->outputType) {
            default:
            case 'json':
                return $response;
            case 'object':
                return json_decode($response);
            case 'array':
                return json_decode($response, true);
        }
    }

    public function setOutputType($type)
    {
        if (!in_array($type, ['json', 'object', 'array'])) {
            throw new Exception('Invalid output type');
        } else {
            $this->outputType = $type;
        }
    }

    /**
     * Getting real ip of requester
     * @return string $ip
     */
    public static function getIp()
    {
        $ip = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}
