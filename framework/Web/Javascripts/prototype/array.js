var $A = Array.from = function(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0; i < iterable.length; i++)
      results.push(iterable[i]);
    return results;
  }
}

Object.extend(Array.prototype, Enumerable);

if (!Array.prototype._reverse)
  Array.prototype._reverse = Array.prototype.reverse;

Object.extend(Array.prototype, {
  _each: function(iterator) {
    for (var i = 0; i < this.length; i++)
      iterator(this[i]);
  },
  
  clear: function() {
    this.length = 0;
    return this;
  },
  
  first: function() {
    return this[0];
  },
  
  last: function() {
    return this[this.length - 1];
  },
  
  compact: function() {
    return this.select(function(value) {
      return value != undefined || value != null;
    });
  },
  
  flatten: function() {
    return this.inject([], function(array, value) {
      return array.concat(value && value.constructor == Array ?
        value.flatten() : [value]);
    });
  },
  
  without: function() {
    var values = $A(arguments);
    return this.select(function(value) {
      return !values.include(value);
    });
  },
  
  indexOf: function(object) {
    for (var i = 0; i < this.length; i++)
      if (this[i] == object) return i;
    return -1;
  },
  
  reverse: function(inline) {
    return (inline !== false ? this : this.toArray())._reverse();
  },
  
  inspect: function() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }
});
