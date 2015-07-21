<?php
namespace Bpi\Sdk\Tests\Unit;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;
use Bpi\Sdk\Document;
use Bpi\Sdk\Authorization;

class PropertiesTest extends \PHPUnit_Framework_TestCase
{
    protected function createMockDocument($fixture)
    {
        $client = $this->getMock('Goutte\Client');
        $client->expects($this->at(0))
              ->method('request')
              ->will($this->returnValue(new Crawler(file_get_contents(__DIR__ . '/Fixtures/' . $fixture . '.bpi'))));

        $client->expects($this->any())->method('getResponse')->will($this->returnValue(new Response()));

        $doc = new Document($client, new Authorization(mt_rand(), mt_rand(), mt_rand()));
        $doc->loadEndpoint('http://example.com');
        return $doc;
    }

    public function testWalkProperties()
    {
        $doc = $this->createMockDocument('Node');
        $properties = array();
        $doc->walkProperties(function($e) use(&$properties) {
            $properties[] = $e;
        });

        $this->assertEquals('title', $properties[0]['name']);
        $this->assertEquals('TITLE', $properties[0]['@value']);
        $this->assertEquals('syndicated', $properties[1]['name']);
        $this->assertEquals('1', $properties[1]['@value']);
        $this->assertEquals('teaser', $properties[2]['name']);
        $this->assertEquals('TEASER', $properties[2]['@value']);
    }

    public function testGetPropertiesFromCollection()
    {
        $doc = $this->createMockDocument('Collection');

        $i = 0;
        foreach ($doc as $item)
        {
            switch ($i)
            {
                // collection
                case 0:
                    $properties = array();
                    $doc->walkProperties(function($e) use($properties) {
                        $properties[] = $e;
                    });
                    $this->assertEmpty($properties);

                    break;
                // entity
                case 1:
                  $properties = array();
                  $doc->walkProperties(function($e) use(&$properties) {
                      $properties[] = $e;
                  });

                  $this->assertEquals('title', $properties[0]['name']);
                  $this->assertEquals('COLLECTION_TITLE', $properties[0]['@value']);
                  $this->assertEquals('teaser', $properties[1]['name']);
                  $this->assertEquals('COLLECTION_TEASER', $properties[1]['@value']);
                  $this->assertEquals('syndicated', $properties[2]['name']);
                  $this->assertEquals('4', $properties[2]['@value']);

                  break;
                case 2: break;
                default:
                    $this->fail('Unexpected');
            }
            $i++;
        }
    }

    public function testPropertiesWhileIterating()
    {
        $doc = $this->createMockDocument('Collection');
        $doc->reduceItemsByAttr('type', 'entity');

        $items = array();
        foreach($doc as $item)
        {
          $item_properties = array();
          $item->walkProperties(function($e) use(&$item_properties) {
              $item_properties[] = $e;
          });
          $items[] = $item_properties;
        }

        $this->assertEquals($doc->count(), count($items));
        $this->assertEquals(3, count($items[0]));
        $this->assertEquals(3, count($items[1]));
        $this->assertEquals('COLLECTION_TITLE', $items[0][0]['@value']);
        $this->assertEquals('COLLECTION_TEASER', $items[0][1]['@value']);
        $this->assertEquals('4', $items[0][2]['@value']);
        $this->assertEquals('COLLECTION_TITLE_BRAVO', $items[1][0]['@value']);
        $this->assertEquals('COLLECTION_TEASER_BRAVO', $items[1][1]['@value']);
        $this->assertEquals('4', $items[1][2]['@value']);
    }
}
