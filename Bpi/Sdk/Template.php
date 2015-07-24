<?php

namespace Bpi\Sdk;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Template contains methods to render entity.
 */
class Template
{
    /**
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * @var array of fields.
     */
    protected $fields = array();

    /**
     * Initiate object.
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
     * @returns bool
     */
    protected function testConsistency()
    {
        try {
            $this->crawler->attr('href');
            $this->crawler->filter('param');
        } catch (\InvalidArgumentException $e) {
            throw new Exception\UndefinedHypermedia();
        }

        return true;
    }

    /**
     * Iterate over fields and prepare flat array with data.
     *
     * @return array of data
     */
    protected function render()
    {
        $data = array();
        foreach ($this->fields as $field) {
            $field->assignToList($data);
        }

        return $data;
    }

    /**
     * Send post request to WS.
     *
     * @param \Bpi\Sdk\Document $document
     */
    public function post(Document $document)
    {
        $document->request('POST', $this->crawler->attr('href'), $this->render());
    }

    /**
     * Walk for each field in template.
     *
     * @param callback $callback
     *
     * @return \Bpi\Sdk\Template
     */
    public function eachField($callback)
    {
        foreach ($this->crawler->filter('field') as $node) {
            $this->fields[] = $field = new Template\Field($node);
            $callback($field);
        }

        return $this;
    }
}
