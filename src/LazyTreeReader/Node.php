<?php

namespace LazyTreeReader;

use LazyTreeReader\Cache;

/**
 * A representation of a node on the backend. The object's parent/child nodes are not loaded from
 * the backend until they are requested.
 */
class Node {
    
    const UNKNOWN = -1;

    /**
     * @var string
     */
    protected $_id;

    /**
     * @var int
     */
    protected $_order = null;

    /**
     * @var bool
     */
    protected $_isPopulated = false;

    /**
     * @var Node
     */
    protected $_parentNode = self::UNKNOWN;

    /**
     * @var array
     */
    protected $_childNodesById = array();

    /**
     * @var bool
     */
    protected $_childrenFrozen = false;

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @param string $id
     * @param int $order Used to sort sibling nodes by default
     */
    public function __construct($id)
    {
        $this->_id = (string) $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * @param Cache $cache
     * @return Node
     */
    public function setCache(Cache $cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * @param $order
     * @return Node
     */
    public function setOrder($order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        if (! is_numeric($this->_order)) {
            $this->_order = $this->_cache->getBackend()->getOrder($this);
        }
        return $this->_order;
    }

    /**
     * @return Node|null
     */
    public function getParentNode()
    {
        if ($this->_parentNode === self::UNKNOWN) {
            $this->loadParent();
        }
        return $this->_parentNode;
    }

    /**
     * @param string $id
     * @param bool $reciprocate Add this node as a child node of the parent
     * @return Node
     */
    public function setParentId($id, $reciprocate)
    {
        $this->_parentNode = $this->_cache->setNodeExistence($id, true);
        if ($reciprocate) {
            $this->_parentNode->addChildNodeIds($this->_id, false);
        }
        return $this;
    }

    /**
     * @param bool $requireAll make sure to return all child nodes known by backend
     * @return array
     */
    public function getChildNodes($requireAll = true)
    {
        if ($requireAll && ! $this->_childrenFrozen) {
            $this->loadChildNodes();
        }
        return $this->_childNodesById;
    }

    /**
     * Add child nodes. Call as few times as possible because this has to re-sort the
     * node list each time.
     * @param array|string $ids
     * @param bool $reciprocate Set the parent of the added nodes
     * @param array $orders include this to set the orders of the added nodes
     * @return Node
     */
    public function addChildNodeIds($ids, $reciprocate, array $orders = array())
    {
        if (! $this->_childrenFrozen) {
            foreach ((array) $ids as $id) {
                $order = array_shift($orders);
                $hasOrder = is_numeric($order);
                if ($hasOrder || ! isset($this->_childNodesById[$id])) {
                    $node = $this->_cache->setNodeExistence($id, true);
                    if ($hasOrder) {
                        $node->setOrder($order);
                    } else {
                        // force order to be loaded from backend
                        $node->getOrder();
                    }
                    if ($reciprocate) {
                        $node->setParentId($this->_id, false);
                    }
                    $this->_childNodesById[$id] = $node;
                }
            }
        }
        uasort($this->_childNodesById, array(get_class($this), 'compare'));
        return $this;
    }

    /**
     * Declare that all child nodes from the backend are accounted for.
     */
    public function freezeChildNodes()
    {
        $this->_childrenFrozen = true;
    }

    /**
     * @param Node $a
     * @param Node $b
     * @return int
     */
    public static function compare(Node $a, Node $b)
    {
        if ($a->_order == $b->_order) {
            return 0;
        }
        return ($a->_order < $b->_order) ? -1 : 1;
    }

    /**
     * Set parent node from backend storage
     * @return Node
     */
    public function loadParent()
    {
        $id = $this->_cache->getBackend()->getParentId($this);
        return $this->setParentId($id, true);
    }

    /**
     * Set child nodes from backend storage
     * @return Node
     */
    public function loadChildNodes()
    {
        $ids = $this->_cache->getBackend()->getChildNodeIds($this);
        $orders = array_keys($ids);
        $this->addChildNodeIds($ids, true, $orders);
        $this->_childrenFrozen = true;
        return $this;
    }

    /**
     * Populate the node from backend storage
     * @return Node
     */
    public function populate()
    {
        if (! $this->_isPopulated) {
            $this->_cache->getBackend()->populate($this);
            $this->_isPopulated = true;
        }
        return $this;
    }
}