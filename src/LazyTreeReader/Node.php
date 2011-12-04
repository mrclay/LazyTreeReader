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
     * @var bool
     */
    protected $_attributesLoaded = false;

    /**
     * @var Node
     */
    protected $_parentNode = self::UNKNOWN;

    /**
     * @var Node
     */
    protected $_prevSibling = self::UNKNOWN;

    /**
     * @var Node
     */
    protected $_nextSibling = self::UNKNOWN;

    /**
     * @var array
     */
    protected $_childNodesById = array();

    /**
     * @var bool
     */
    protected $_allChildrenKnown = false;

    /**
     * @var Node
     */
    protected $_firstChild = self::UNKNOWN;

    /**
     * @var Node
     */
    protected $_lastChild = self::UNKNOWN;

    /**
     * @var array
     */
    protected $_attributes = array();

    /**
     * @var Cache
     */
    protected $_cache;

    /**
     * @param string $id
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
     * @return bool
     */
    public function isParentKnown()
    {
        return ($this->_parentNode !== self::UNKNOWN);
    }

    /**
     * @param string $id
     * @param bool $reciprocate Add this node as a child node of the parent
     * @return Node
     */
    public function setParentId($id, $reciprocate = true)
    {
        if ($id === null) {
            $this->_parentNode = null;
        } else {
            $this->_parentNode = $this->_cache->setNodeExistence($id, true);
            if ($reciprocate) {
                $this->_parentNode->addChildNodeIds($this->_id, false);
            }
        }
        return $this;
    }
    
    /**
     * @return Node|null
     */
    public function getPreviousSibling()
    {
        if ($this->_prevSibling === self::UNKNOWN) {
            $this->loadPreviousSibling();
        }
        return $this->_parentNode;
    }

    /**
     * @return Node|null
     */
    public function getNextSibling()
    {
        if ($this->_nextSibling === self::UNKNOWN) {
            $this->loadNextSibling();
        }
        return $this->_nextSibling;
    }

    /**
     * @return bool
     */
    public function isPreviousSiblingKnown()
    {
        return ($this->_prevSibling !== self::UNKNOWN);
    }

    /**
     * @return bool
     */
    public function isNextSiblingKnown()
    {
        return ($this->_nextSibling !== self::UNKNOWN);
    }

    /**
     * @param string $id
     * @param bool $reciprocate set a back reference on the previous sibling
     * @param bool $notifyParent set up the parent/child relationship for the previous sibling (if we know the parent)
     * @return Node
     */
    public function setPreviousSiblingId($id, $reciprocate = true, $notifyParent = true)
    {
        if ($id === null) {
            $this->_prevSibling = null;
        } else {
            $this->_prevSibling = $this->_cache->setNodeExistence($id, true);
            if ($reciprocate) {
                $this->_prevSibling->setNextSiblingId($this->_id, false, false);
            }
            if ($notifyParent && $this->_parentNode instanceof Node) {
                $this->_prevSibling->setParentId($this->_parentNode->getId());
            }
        }
        return $this;
    }

    /**
     * @param string $id
     * @param bool $reciprocate set a back reference on the next sibling
     * @param bool $notifyParent set up the parent/child relationship for the next sibling (if we know the parent)
     * @return Node
     */
    public function setNextSiblingId($id, $reciprocate = true, $notifyParent = true)
    {
        if ($id === null) {
            $this->_nextSibling = null;
        } else {
            $this->_nextSibling = $this->_cache->setNodeExistence($id, true);
            if ($reciprocate) {
                $this->_nextSibling->setPreviousSiblingId($this->_id, false, false);
            }
            if ($notifyParent && $this->_parentNode instanceof Node) {
                $this->_nextSibling->setParentId($this->_parentNode->getId());
            }
        }
        return $this;
    }

    /**
     * Get nodes that are known to be children (may not include all in storage, and may not be in order)
     * @return array
     */
    public function getKnownChildNodes()
    {
        return $this->_childNodesById;
    }

    /**
     * @param string $id
     * @return Node|null
     */
    public function getKnownChildNodeById($id)
    {
        return isset($this->_childNodesById[$id]) ? $this->_childNodesById[$id] : null;
    }

    /**
     * All nodes from storage and in proper order
     * @return array
     */
    public function getChildNodes()
    {
        return array_values($this->getChildNodesById());
    }

    /**
     * All nodes from storage and in proper order
     * @return array
     */
    public function getChildNodesById()
    {
        if (! $this->_allChildrenKnown) {
            $this->loadChildNodes();
        }
        return $this->_childNodesById;
    }

    /**
     * @return Node|null
     */
    public function getFirstChild()
    {
        if ($this->_firstChild === self::UNKNOWN) {
            if ($this->_allChildrenKnown) {
                // get from childNodes
                if ($this->_childNodesById) {
                    list($id, $node) = each($this->_childNodesById);
                    $this->_firstChild = $node;
                } else {
                    $this->_firstChild = null;
                }
            } else {
                $this->loadFirstChild();
            }
        }
        return $this->_firstChild;
    }

    /**
     * @return Node|null
     */
    public function getLastChild()
    {
        if ($this->_lastChild === self::UNKNOWN) {
            if ($this->_allChildrenKnown) {
                // get from childNodes
                if ($this->_childNodesById) {
                    $keys = array_keys($this->_childNodesById);
                    $this->_lastChild = $this->_childNodesById[array_pop($keys)];
                } else {
                    $this->_lastChild = null;
                }
            } else {
                $this->loadLastChild();
            }
        }
        return $this->_lastChild;
    }

    /**
     * Does the node know about all child nodes in storage?
     * @return bool
     */
    public function allChildrenKnown()
    {
        return $this->_allChildrenKnown;
    }

    /**
     * Add child nodes.
     * @param array|string $ids
     * @param bool $setParents Set the parent of the added nodes
     * @param bool $idsAreOrdered if true, the nodes will be tied to each other with sibling relationships
     * @return Node
     */
    public function addChildNodeIds($ids, $setParents = true, $idsAreOrdered = false)
    {
        if (! $this->_allChildrenKnown) {
            $lastNode = null;
            foreach ((array) $ids as $id) {
                if ($idsAreOrdered || ! array_key_exists($id, $this->_childNodesById)) {
                    $node = $this->_cache->setNodeExistence($id, true);
                    if ($setParents) {
                        $node->setParentId($this->_id, false);
                    }
                    if ($idsAreOrdered && $lastNode) {
                        $lastNode->setNextSiblingId($node->getId(), true);
                    }
                    $this->_childNodesById[$id] = $node;
                    $lastNode = $node;
                }
            }
        }
        return $this;
    }

    /**
     * Set all the child nodes. $ids must be complete and in order
     * @param array $ids
     * @return Node
     */
    public function setChildNodeIds(array $ids)
    {
        // since some child nodes may already exist out of order, in order for childNodes to end up
        // in the correct order, we must empty the array first
        $this->_childNodesById = array();

        if (count($ids) > 0) {
            // add child nodes, setting their parent/child and inner prev/next relationships
            $this->addChildNodeIds($ids, true, true);
            // addChildNodeIds does not set the first child's previousSibling nor the last's nextSibling
            // so we manually set these to null
            $this->getKnownChildNodeById($ids[0])->setPreviousSiblingId(null, false, false);
            $this->getKnownChildNodeById(array_pop($ids))->setNextSiblingId(null, false, false);
        }
        $this->_allChildrenKnown = true;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isAttributeKnown($name)
    {
        return array_key_exists($name, $this->_attributes[$name]);
    }

    /**
     * @param string $name
     * @param $value
     * @return Node
     */
    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (! array_key_exists($name, $this->_attributes[$name])) {
            $this->loadAttribute($name);
        }
        return $this->_attributes[$name];
    }

    /**
     * Set parent node from backend storage
     * @return Node|null
     */
    public function loadParent()
    {
        $id = $this->_cache->getBackend()->getParentId($this);
        return $this->setParentId($id);
    }

    /**
     * Set all child nodes from backend storage
     * @return Node
     */
    public function loadChildNodes()
    {
        $ids = $this->_cache->getBackend()->getChildNodeIds($this);
        return $this->setChildNodeIds($ids);
    }

    /**
     * Set previous sibling node from backend storage
     * @return Node|null
     */
    public function loadPreviousSibling()
    {
        $id = $this->_cache->getBackend()->getPreviousSiblingId($this);
        return $this->setPreviousSiblingId($id);
    }

    /**
     * Set next sibling node from backend storage
     * @return Node|null
     */
    public function loadNextSibling()
    {
        $id = $this->_cache->getBackend()->getNextSiblingId($this);
        return $this->setNextSiblingId($id);
    }

    /**
     * @return Node
     */
    public function loadFirstChild()
    {
        $id = $this->_cache->getBackend()->getFirstChildId($this);
        if (null === $id) {
            $this->_allChildrenKnown = true;
        } else {
            $this->addChildNodeIds($id);
            $this->_firstChild = $this->getKnownChildNodeById($id)->setPreviousSiblingId(null);
        }
        return $this;
    }

    /**
     * @return Node
     */
    public function loadLastChild()
    {
        $id = $this->_cache->getBackend()->getLastChildId($this);
        if (null === $id) {
            $this->_allChildrenKnown = true;
        } else {
            $this->addChildNodeIds($id);
            $this->_lastChild = $this->getKnownChildNodeById($id)->setNextSiblingId(null);
        }
        return $this;
    }

    /**
     * Load attribute from backend storage
     * @param string $name
     * @return Node
     */
    public function loadAttribute($name)
    {
        $this->_attributes[$name] = $this->_cache->getBackend()->getAttribute($this, $name);
        return $this;
    }

    /**
     * Load all known attributes from backend storage
     * @return Node
     */
    public function loadAttributes()
    {
        if (! $this->_attributesLoaded) {
            // send the backend a list of attributes we already have, so the backend can possibly
            // skip re-loading them.
            $presentAttributeNames = array_keys($this->_attributes);
            $newAttributes = $this->_cache->getBackend()->getMissingAttributes($this, $presentAttributeNames);
            $this->_attributes = array_merge($this->_attributes, $newAttributes);
            $this->_attributesLoaded = true;
        }
        return $this;
    }

    /**
     * @param bool $reciprocate remove the parent's child reference
     * @return Node
     */
    public function forgetParent($reciprocate = true)
    {
        if ($reciprocate && $this->_parentNode instanceof Node) {
            $this->_parentNode->forgetChildNode($this, false);
        }
        $this->_parentNode = self::UNKNOWN;
        return $this;
    }

    /**
     * @param Node $node
     * @param bool $reciprocate remove the parent reference from the child
     * @return Node
     */
    public function forgetChildNode(Node $node, $reciprocate = true)
    {
        unset($this->_childNodesById[$node->getId()]);
        if ($reciprocate) {
            $node->forgetParent(false);
        }
        if ($this->_firstChild === $node) {
            $this->_firstChild = self::UNKNOWN;
        }
        if ($this->_lastChild === $node) {
            $this->_lastChild = self::UNKNOWN;
        }
        $this->_allChildrenKnown = false;
        return $this;
    }

    /**
     * @param bool $reciprocate remove the parent reference from each child
     * @return Node
     */
    public function forgetAllChildren($reciprocate = true)
    {
        if ($reciprocate) {
            foreach ($this->_childNodesById as $node) {
                /* @var Node $node */
                $node->forgetParent(false);
            }
        }
        $this->_childNodesById = array();
        $this->_firstChild = self::UNKNOWN;
        $this->_lastChild = self::UNKNOWN;
        $this->_allChildrenKnown = false;
        return $this;
    }

    /**
     * @param bool $reciprocate remove the previous sibling's back reference
     * @return Node
     */
    public function forgetPreviousSibling($reciprocate = true)
    {
       if ($reciprocate && $this->_prevSibling instanceof Node) {
            $this->_prevSibling->forgetNextSibling(false);
        }
        $this->_prevSibling = self::UNKNOWN;
        return $this;
    }

    /**
     * @param bool $reciprocate remove the next sibling's back reference
     * @return Node
     */
    public function forgetNextSibling($reciprocate = true)
    {
        if ($reciprocate && $this->_nextSibling instanceof Node) {
            $this->_nextSibling->forgetPreviousSibling(false);
        }
        $this->_nextSibling = self::UNKNOWN;
        return $this;
    }

    /**
     * @param string $name
     * @return Node
     */
    public function forgetAttribute($name)
    {
        unset($this->_attributes[$name]);
        return $this;
    }
}