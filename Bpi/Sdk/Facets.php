<?php
/**
 * Created by PhpStorm.
 * User: Max
 * Date: 21.07.2015
 * Time: 12:10
 */

namespace Bpi\Sdk;
use Bpi\Sdk\Item\Facet;

/**
 * Class Facets
 * @package Bpi\Sdk
 *
 * All facets object.
 */
class Facets {

    /**
     * Array of build facets
     * @var array
     */
    protected $facets;

    /**
     * Add built facet
     * @param $facet \Bpi\Sdk\Item\Facet
     * @return $this
     */
    public function addFacets($facet)
    {
        $this->facets[] = $facet;
        return $this;
    }

    /**
     * Get built facets
     * @return array
     */
    public function getFacets()
    {
        return $this->facets;
    }

    /**
     * Builds facets form row response from server
     * @throws \Exception
     */
    public function buildFacets(array $rawFacets)
    {
        if (empty($rawFacets)) {
            throw new \Exception('Raw facets empty.');
        }

        foreach ($rawFacets as $facetsData) {
            $facet = new Facet();
            $facet->setFacetName($facetsData['name']->__toString());

            foreach ($facetsData->properties->children() as $facetData) {
                $facet->setFacetTerms($facetData['name']->__toString(), $facetData->__toString());
            }
//            $facet->setFacet();
            $this->addFacets($facet);
        }
    }
}
