<?php

namespace LazyTreeReader;

use LazyTreeReader\Node;
use LazyTreeReader\Cache;

/**
 * An abstract class for querying backend storage of nodes.
 *
 * When implementing the abstract methods, you're encouraged to inject any extra data into the cache
 * (e.g. calling setNodeExistence and setting node properties) before returning. You can optimize
 * this process to minimize slow backend calls.
 */
abstract class Backend {

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @param Cache $cache
     * @return Backend
     */
    public function setCache(Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * Does a node exist in storage?
     * @abstract
     * @param string $id
     * @return bool
     */
    abstract public function nodeExists($id);

    /**
     * Get an empty Node of your desired class (without a storage call)
     * @abstract
     * @param string $id
     * @return Node
     */
    abstract public function createEmptyNode($id);

    /**
     * Get the parent id (or null) from storage
     * @abstract
     * @param Node $node
     * @return string|null
     */
    abstract public function getParentId(Node $node);

    /**
     * Get the list of child node ids from storage in order
     * @abstract
     * @param Node $node
     * @return array
     */
    abstract public function getChildNodeIds(Node $node);

    /**
     * @abstract
     * @param Node $node
     * @return void
     */
    abstract public function getOrder(Node $node);

    /**
     * Fully populate the node from storage (after returning, the node should have all
     * the data its designed to store from storage)
     * @param Node $node
     * @return void
     */
    public function populate(Node $node)
    {
        
    }
}