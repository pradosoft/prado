var $A = Array.from = function(iterable) {
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

Object.extend(Array.prototype, {
  _each: function(iterator) {
    for (var i = 0; i < this.length; i++)
      iterator(this[i]);
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
      return array.concat(value.constructor == Array ?
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
    return false;
  },
  
  reverse: function() {
    var result = [];
    for (var i = this.length; i > 0; i--)
      result.push(this[i-1]);
    return result;
  },
  
  inspect: function() {
    return '[' + this.map(Object.inspect).join(', ') + ']';
  }
});
