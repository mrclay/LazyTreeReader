<?php

namespace LazyTreeReader;

use LazyTreeReader\Backend;
use LazyTreeReader\Node;

/**
 * A cache for holding a reference to all known nodes.
 *
 * You may wish to serialize the cache between requests to minimize backend access.
 *
 * By requiring all nodes be fetched from here, we guarantee that each node has a back reference
 * to the cache and that there are no duplicates of nodes with the same ID.
 */
class Cache {

    /**
     * Note, this property is not serialized
     * @var Backend
     */
    protected $_backend;

    /**
     * @var array
     */
    protected $_nodesById = array();

    /**
     * @param Backend $backend
     */
    public function __construct(Backend $backend)
    {
        $this->setBackend($backend);
    }

    /**
     * @param Backend $backend
     * @return Cache
     */
    public function setBackend(Backend $backend)
    {
        $this->_backend = $backend;
        $backend->setCache($this);
        return $this;
    }
    
    /**
     * @return Backend
     */
    public function getBackend()
    {
        return $this->_backend;
    }

    /**
     * Clear the cache
     * @return Cache
     */
    public function clear()
    {
        $this->_nodesById = array();
        return $this;
    }

    /**
     * Get a node or null from the cache. If we don't know of its existence, check the backend.
     * @param string $id
     * @return Node|null
     */
    public function getNodeById($id)
    {
        if (! isset($this->_nodesById[$id])) {
            $this->setNodeExistence($id, $this->_backend->nodeExists($id));
        }
        return $this->_nodesById[$id];
    }

    /**
     * Notify the cache that a node with a particular id does or doesn't exist. If it exists, the cache
     * will store a new node and return it.
     * @param string $id
     * @param bool $exists
     * @return Node|null
     */
    public function setNodeExistence($id, $exists)
    {
        if (! isset($this->_nodesById[$id])) {
            if ($exists) {
                $node = $this->_backend->createEmptyNode($id);
                $node->setCache($this);
                $this->_nodesById[$id] = $node;
            } else {
                $this->_nodesById[$id] = null;
            }
        }
        return $this->_nodesById[$id];
    }

    /**
     * When serialized, don't store backend object. User must reconnect it via setBackend()
     * @return array
     */
    public function __sleep()
    {
        return array('_nodesById');
    }
}