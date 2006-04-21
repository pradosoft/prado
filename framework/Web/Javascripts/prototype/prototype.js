/*  Prototype JavaScript framework, version <%= PROTOTYPE_VERSION %>
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 *
/*--------------------------------------------------------------------------*/

var Prototype = {
  Version: '1.50',
  ScriptFragment: '(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)',
  
  emptyFunction: function() {},
  K: function(x) {return x}
}

/*
<%= include 'base.js', 'string.js' %>

<%= include 'enumerable.js', 'array.js', 'hash.js', 'range.js' %>

<%= include 'ajax.js', 'dom.js', 'selector.js', 'form.js', 'event.js', 'position.js' %>

*/