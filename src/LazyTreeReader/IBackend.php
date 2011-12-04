<?php

namespace LazyTreeReader;

/**
 * Interface for class to query backend node storage.
 *
 * When implementing methods, you're encouraged to inject any extra data into the cache
 * (e.g. calling setNodeExistence and setting node attributes) before returning. You can optimize
 * this process to minimize slow backend calls.
 */
interface IBackend {

    /**
     * @param Cache $cache
     * @return Backend
     */
    public function setCache(Cache $cache);

    /**
     * Does a node exist in storage?
     * @param string $id
     * @return bool
     */
    public function nodeExists($id);

    /**
     * Get an empty Node of your desired class (without a storage call)
     * @param string $id
     * @return Node
     */
    public function createEmptyNode($id);

    /**
     * Get the parent id (or null) from storage
     * @param Node $node
     * @return string|null
     */
    public function getParentId(Node $node);

    /**
     * Get the correctly ordered list of all child node ids from storage
     * @param Node $node
     * @return array
     */
    public function getChildNodeIds(Node $node);

    /**
     * @param Node $node
     * @return string|null
     */
    public function getFirstChildId(Node $node);

    /**
     * @param Node $node
     * @return string|null
     */
    public function getLastChildId(Node $node);

    /**
     * @param Node $node
     * @return string|null
     */
    public function getPreviousSiblingId(Node $node);

    /**
     * @param Node $node
     * @return string|null
     */
    public function getNextSiblingId(Node $node);

    /**
     * @param Node $node
     * @param string $name
     * @return mixed
     */
    public function getAttribute(Node $node, $name);

    /**
     * Get all missing attributes of this node
     * @param Node $node
     * @param array $presentAttributeNames names of attributes that the Node has already loaded
     * @return array
     */
    public function getMissingAttributes(Node $node, array $presentAttributeNames = array());
}
