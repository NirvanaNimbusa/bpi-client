<?php

namespace Bpi\Sdk;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Document represent methods for entity processing.
 */
class Document implements \Iterator, \Countable
{
    /**
     * @var \Goutte\Client crawler library
     */
    protected $http_client;

    /**
     * @var \Bpi\Sdk\Authorization authorization credentials
     */
    protected $authorization;

    /**
     * @var \Symfony\Component\DomCrawler\Crawler document crawler
     */
    protected $crawler;

    /**
     * @param \Goutte\Client         $client
     * @param \Bpi\Sdk\Authorization $authorization
     */
    public function __construct(Client $client, Authorization $authorization)
    {
        $this->http_client = $client;
        $this->authorization = $authorization;
    }

    /**
     * Load web service.
     *
     * @param string $endpoint API URL
     *
     * @return \Bpi\Sdk\Document same instance
     */
    public function loadEndpoint($endpoint)
    {
        $this->request('GET', $endpoint);

        return $this;
    }

    /**
     * Gateway to make direct requests to API.
     *
     * @param string $method
     * @param string $uri
     * @param array  $params
     *
     * @throws Exception\HTTP\ClientError
     * @throws Exception\HTTP\ServerError
     * @throws Exception\HTTP\Error
     *
     * @return \Bpi\Sdk\Document same instance
     */
    public function request($method, $uri, array $params = array())
    {
        $headers = array(
            'HTTP_Auth' => $this->authorization->toHTTPHeader(),
            'HTTP_Content_Type' => 'application/vnd.bpi.api+xml',
        );

        $this->crawler = $this->http_client->request($method, $uri, $params, array(), $headers);
        $this->crawler = $this->crawler->filter('bpi > item');
        $this->crawler->rewind();

        if ($this->status()->isError()) {
            if ($this->status()->isClientError()) {
                throw new Exception\HTTP\ClientError($this->http_client->getResponse()->getContent(), $this->status()->getCode());
            }

            if ($this->status()->isServerError()) {
                throw new Exception\HTTP\ServerError($this->http_client->getResponse()->getContent(), $this->status()->getCode());
            }

            throw new Exception\HTTP\Error($this->http_client->getResponse()->getContent(), $this->status()->getCode());
        }

        return $this;
    }

    /**
     * Get last response status.
     *
     * @return \Bpi\Sdk\ResponseStatus
     */
    public function status()
    {
        return new ResponseStatus($this->http_client->getResponse()->getStatus());
    }

    /**
     * Dump latest raw response data.
     *
     * @return string response from web service
     */
    public function dumpRawResponse()
    {
        return $this->http_client->getResponse();
    }

    /**
     * Dump latest raw request data.
     *
     * @return string response from wev service
     */
    public function dumpRawRequest()
    {
        return print_r($this->http_client->getRequest(), true);
    }

    /**
     * Access hypermedia link.
     *
     * @throws Exception\UndefinedHypermedia
     *
     * @param string $rel
     *
     * @return \Bpi\Sdk\Link
     */
    public function link($rel)
    {
        try {
            $crawler = $this->crawler
                ->filter("hypermedia > link[rel='{$rel}']")
                ->first();

            return new Link($crawler);
        } catch (\InvalidArgumentException $e) {
            throw new Exception\UndefinedHypermedia(sprintf('There is no such link [%s]', $rel));
        }
    }

    /**
     * Click on link.
     *
     * @param \Bpi\Sdk\Link $link
     */
    public function followLink(Link $link)
    {
        $link->follow($this);
    }

    /**
     * Access hypermedia query.
     *
     * @throws Exception\UndefinedHypermedia
     *
     * @param string $rel
     *
     * @return \Bpi\Sdk\Query
     */
    public function query($rel)
    {
        try {
            $query = $this->crawler
                ->filter("hypermedia > query[rel='{$rel}']")
                ->first();

            return new Query($query);
        } catch (\InvalidArgumentException $e) {
            throw new Exception\UndefinedHypermedia(sprintf('There is no query [%s]', $rel));
        }
    }

    /**
     * Send query.
     *
     * @param \Bpi\Sdk\Query $query
     * @param array          $params
     */
    public function sendQuery(Query $query, $params)
    {
        $query->send($this, $params);
    }

    /**
     * Access hypermedia template.
     *
     * @throws Exception\UndefinedHypermedia
     *
     * @param string $rel
     *
     * @return \Bpi\Sdk\Template
     */
    public function template($rel)
    {
        try {
            $query = $this->crawler
                ->filter("hypermedia > template[rel='{$rel}']")
                ->first();

            return new Template($query);
        } catch (\InvalidArgumentException $e) {
            throw new Exception\UndefinedHypermedia(sprintf('There is no such template [%s]', $rel));
        }
    }

    /**
     * Post rendered template.
     *
     * @param \Bpi\Sdk\Template $template
     */
    public function postTemplate(Template $template)
    {
        $template->post($this);
    }

    /**
     * Checks current item type.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isTypeOf($type)
    {
        return $this->crawler->current()->getAttribute('type') == $type;
    }

    /**
     * Iterates over all properties of current item.
     *
     * @return closure
     */
    public function walkProperties($callback)
    {
        $crawler = new Crawler($this->crawler->current());

        return $crawler->filter('item property')->each(
            function ($e) use ($callback) {
                $sxml = simplexml_import_dom($e->getNode(0));
                $callback(current($sxml->attributes()) + array('@value' => (string) $sxml));
            }
        );
    }

    /**
     * Convert xml to php array.
     *
     * @return array
     */
    public function propertiesToArray()
    {
        $properties = array();
        $this->walkProperties(
            function ($p) use (&$properties) {
                $properties[$p['name']] = $p['@value'];
            }
        );

        return $properties;
    }

    /**
     * Finds first matched item by attribute value.
     *
     * @param string $attr
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     *
     * @return \Bpi\Sdk\Document same instance
     */
    public function firstItem($attr, $value)
    {
        $this->crawler = $this->crawler
            ->filter("item[$attr='{$value}']")
            ->first();

        if (!$this->crawler->count()) {
            throw new \InvalidArgumentException(sprintf('Item with attribute "%s" and value "%s" was not found', $attr, $value));
        }

        $this->crawler->rewind();

        return $this;
    }

    /**
     * Filter items (<item> tags) by attribute values.
     *
     * @param string $attr
     * @param mixed  $value
     *
     * @throws \Bpi\Sdk\Exception\EmptyList
     *
     * @return \Bpi\Sdk\Document same instance
     */
    public function reduceItemsByAttr($attr, $value)
    {
        $this->crawler = $this->crawler->filter("item[$attr='{$value}']");

        if (!$this->crawler->count()) {
            throw new Exception\EmptyList(sprintf('No items remain after reduce was made by attr [%s], value [%s]', $attr, $value));
        }

        $this->crawler->rewind();

        return $this;
    }

    /**
     * Clear previous response if any.
     */
    public function clear()
    {
        $this->crawler = new Crawler();
    }

    /**
     * Iterator interface implementation.
     *
     * @group Iterator
     */
    public function rewind()
    {
        $this->crawler->rewind();
    }

    /**
     * Returns same instance but with internal pointer to current item in collection.
     *
     * @group Iterator
     *
     * @return \Bpi\Sdk\Document will return same instance
     */
    public function current()
    {
        return $this;
    }

    /**
     * Key of current iteration position.
     *
     * @group Iterator
     *
     * @return string
     */
    public function key()
    {
        return $this->crawler->key();
    }

    /**
     * Iterate to next item.
     *
     * @group Iterator
     */
    public function next()
    {
        $this->crawler->next();
    }

    /**
     * Checks if is ready for iteration.
     *
     * @group Iterator
     *
     * @return bool
     */
    public function valid()
    {
        return $this->crawler->valid();
    }

    /**
     * Length of items in document.
     *
     * @group Iterator
     *
     * @return int
     */
    public function count()
    {
        return $this->crawler->count();
    }
}
