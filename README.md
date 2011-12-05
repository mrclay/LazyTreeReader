# LazyTreeReader

This is a PHP 5.3 library designed to lazily read nodes, and their relationships and attributes from a static hierarchical tree. E.g. You have a tree so large that loading the tree in memory would be impractical/impossible/slow, but it would be useful to access and traverse a portion of it.

## Design

The IBackend interface requires you to implement methods allowing the system to fetch tree data when needed. This allows the library to support any backend representation of a tree (the only limitation is that each node must map to a unique string identifier) and allows you to optimize your requests to make the most of expensive backend calls (e.g. you may want to preemptively load attributes for returned nodes).

The built-in Node class (extensible) is lightweight--it only knows its "id", but knows how to use the backend to load attributes and related nodes as needed.

The Cache holds references to all known-about nodes, and can be serialized between requests and re-attached to the backend later. By forcing all node look-ups through the cache (you can't directly set a node's parent/children), you make sure that all parent/child node references are properly transitive; you can start building up the cache from the top and bottom of the tree and the nodes will connect properly when they meet.

## Limitations

* You must manage the size of the cached nodes and their attributes. The node has methods to "forget" attributes and relationships or you can remove nodes completely from the cache. Worst case you can clear the cache.
* The cache and nodes are designed to be static representations of a static backend tree, so there's no built-in mechanism to monitor changes in the backend, move/remove nodes, etc. All you can do is clear the cache periodically or when you suspect the tree/nodes have changed.
* The strategy for loading/ordering child nodes is clunky.

## License

Copyright (c) 2011, Stephen Clay and other collaborators
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* Neither the name Stephen Clay nor the names of his contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE AFOREMENTIONED PARTIES BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.