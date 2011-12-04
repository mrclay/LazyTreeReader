<?php

namespace LazyTreeReader;

use LazyTreeReader\IBackend;
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
     * @var IBackend
     */
    protected $_backend;

    /**
     * @var array
     */
    protected $_nodesById = array();

    /**
     * @param IBackend $backend
     */
    public function __construct(IBackend $backend)
    {
        $this->setBackend($backend);
    }

    /**
     * @param IBackend $backend
     * @return Cache
     */
    public function setBackend(IBackend $backend)
    {
        $this->_backend = $backend;
        $backend->setCache($this);
        return $this;
    }
    
    /**
     * @return IBackend
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
     * Fetch a node from the backend (or the cache if available)
     * @param string $id
     * @return Node|null
     */
    public function fetchNodeById($id)
    {
        if (! array_key_exists($id, $this->_nodesById)) {
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
        if (! array_key_exists($id, $this->_nodesById)) {
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
     * @return array
     */
    public function getCachedNodes()
    {
        return $this->_nodesById;
    }

    /**
     * @param Node $node
     * @param bool $removeEntireBranch
     * @return Cache
     */
    public function removeNode(Node $node, $removeEntireBranch = false)
    {
        if ($removeEntireBranch) {
            foreach ($node->getKnownChildNodes() as $child) {
                $this->removeNode($child, true);
            }
        }
        // remove references to the node so can be CGed
        $node->forgetParent();
        $node->forgetAllChildren();
        $node->forgetPreviousSibling();
        $node->forgetNextSibling();
        unset($this->_nodesById[$node->getId()]);
        return $this;
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