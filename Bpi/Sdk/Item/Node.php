<?php

namespace Bpi\Sdk\Item;

/**
 * Class Node.
 *
 * @package Bpi\Sdk\Item
 *
 * Base node methods
 */
class Node extends BaseItem
{
    /**
     * Get node assets (images).
     *
     * @return array
     */
    public function getAssets()
    {
        $result = array();
        foreach ($this->getProperties() as $key => $val) {
            if (strpos($key, 'asset') !== false) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     * Syndicate node from web service.
     */
    public function syndicate()
    {
        // @todo implementation
    }

    /**
     * Push node to web service.
     */
    public function push()
    {
        // @todo implementation
    }

    /**
     * Mark node as deleted on web service.
     */
    public function delete()
    {
        // @todo implementation
    }
}
