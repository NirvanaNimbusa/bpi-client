<?php

namespace Bpi\Sdk;

use Symfony\Component\DomCrawler\Crawler;
use Bpi\Sdk\Exception as Exception;

/**
 * Class Link contain methods which prepare URI for different type of requests.
 */
class Link
{
    /**
     * Document crawler.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Initiate object.
     *
     * @throws Exception\UndefinedHypermedia
     *
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
        $this->testConsistency();
    }

    /**
     * Try crawler for consistency of data.
     *
     * @throws Exception\UndefinedHypermedia
     *
     * @return bool
     */
    protected function testConsistency()
    {
        try {
            $this->crawler->attr('href');
            $this->crawler->attr('rel');
        } catch (\InvalidArgumentException $e) {
            throw  $e;
        }

        return true;
    }

    /**
     * Call request method.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function follow(Document $document)
    {
        $this->get($document);
    }

    /**
     * Perform HTTP GET for given URI.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function get(Document $document)
    {
        $document->request('GET', $this->crawler->attr('href'));
    }

    /**
     * Perform HTTP POST for given URI.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function post(Document $document)
    {
        $document->request('POST', $this->crawler->attr('href'));
    }

    /**
     * Perform HTTP DELETE for given URI.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function delete(Document $document)
    {
        $document->request('DELETE', $this->crawler->attr('href'));
    }

    /**
     * Perform HTTP PUT for given URI.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function put(Document $document)
    {
        $document->request('PUT', $this->crawler->attr('href'));
    }

    /**
     * Convert properties to array.
     *
     * @return array properties
     */
    public function toArray()
    {
        $properties = array();
        foreach ($this->crawler as $node) {
            foreach ($node->attributes as $attr_name => $attr) {
                $properties[$attr_name] = $attr->value;
            }
        }

        return $properties;
    }
}
