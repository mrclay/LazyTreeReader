# LazyTreeReader

This is a PHP5.3 library designed to help you work with partial trees of nodes. E.g. You have a tree so large that loading the tree in memory would be impractical/impossible/slow, but it would be useful to access and traverse smaller portions of it. LazyTreeReader is a framework for loading unknown nodes and relationships only when needed.

## Design

The Backend class is abstract and must be extended to implement methods allowing the system to fetch node/relationship/attributes when needed. This allows the library to support any backend representation of a tree (the only limitation is that each node must have a unique string id) and for you to optimize your requests (e.g. load multiple nodes at a time).

The built-in Node class (extensible) is lightweight--it only knows its "id". You add a populate() method to your backend to add properties to the node from storage. As you traverse nodes, the nodes call the backend as necessary to supply the parent/children if not already known.

The Cache holds references to all known-about nodes, and can be serialized between requests and re-attached to the backend later. By forcing all node lookups through the cache (you can't directly set a node's parent/children), you make sure that all parent/child node references are propertly transitive; you can start building up the cache from the top and bottom of the tree and the nodes will connect properly when they meet.

## Known Flaws

* The cache can only increase in size as new nodes are known about and populated, so the user will have to manually clear the cache when/if it gets big.
* The cache and nodes are designed to be static representations of a static backend tree, so there's no built-in mechanism to monitor changes in the backend, move/remove nodes, etc. All you can do is clear the cache periodically or when you suspect the tree/nodes have changed.
* The populate() method is a clunky way of loading the node's attributes. Node should probably come with a lazy-loading attributes system, but I got lazy.

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