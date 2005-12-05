/**
ARRAY EXTENSIONS
by Caio Chassot (http://v2studio.com/k/code/)
*/

//function v2studio_com_code()
//{


/** 
 * Searches Array for <b>value</b>. 
 * returns the index of the first item 
 * which matches <b>value</b>, or -1 if not found.
 * searching starts at index 0, or at <b>start</b>, if specified.
 *
 * Here are the rules for an item to match <b>value</b>
 * if strict is false or not specified (default):
 * if <b>value</b> is a:
 *  # <b>function</b>    -> <b>value(item)</b> must be true
 *  # <b>RegExp</b>      -> <b>value.test(item)</b> must be true
 *  # anything else -> <b>item == value</b> must be true
 * @param value the value (function, regexp) to search
 * @param start where to start the search
 * @param strict use strict comparison (===) for everything
 */
Array.prototype.indexOf = function(value, start, strict) {
    start = start || 0;
    for (var i=start; i<this.length; i++) {
        var item = this[i];
        if (strict            ? item === value   :
            isRegexp(value)   ? value.test(item) :
            isFunction(value) ? value(item)      :
            item == value)
            return i;
    }
    return -1;
}

/** 
 * searches Array for <b>value</b> returns the first matched item, or null if not found
 * Parameters work the same as indexOf
 * @see #indexOf
 */
Array.prototype.find = function(value, start, strict) {
    var i = this.indexOf(value, start, strict);
    if (i != -1) return this[i];
    return null
}



/*  A.contains(value [, strict])
/** 
 * aliases: has, include
 * returns true if <b>value</b> is found in Array, otherwise false;
 * relies on indexOf, see its doc for details on <b>value</b> and <b>strict</b>
 * @see #indexOf
 */
Array.prototype.contains = function(value,strict) {
    return this.indexOf(value,0,strict) !== -1;
}


Array.prototype.has     = Array.prototype.contains;

Array.prototype.include = Array.prototype.contains;


/** 
 * counts occurences of <b>value</b> in Array
 * relies on indexOf, see its doc for details on <b>value</b> and <b>strict</b>
 * @see #indexOf
 */ 
Array.prototype.count = function(value, strict) {
    var pos, start = 0, count = 0;
    while ((pos = this.indexOf(value, start, strict)) !== -1) {
        start = pos + 1;
        count++;
    }
    return count;
}


/** 
 * if <b>all</b> is false or not provied:
 *        removes first occurence of <b>value</b> from Array
 *   if <b>all</b> is provided and true:
 *       removes all occurences of <b>value</b> from Array
 *   returns the array
 *   relies on indexOf, see its doc for details on <b>value</b> and <b>strict</b>
 * @see #indexOf
 */
Array.prototype.remove = function(value,all,strict) {
    while (this.contains(value,strict)) {
        this.splice(this.indexOf(value,0,strict),1);
        if (!all) break
    }
    return this;
}



/*  A.merge(a [, a]*)
    Append the contents of provided arrays into the current
    takes: one or more arrays
    returns: current array (modified)
*/
Array.prototype.merge = function() {
    var a = [];
    for (var i=0; i<arguments.length; i++)
        for (var j=0; j<arguments[i].length; j++)
            a.push(arguments[i][j]);
    for (var i=0; i<a.length; i++) this.push(a[i]);
    return this
}



/*  A.min()
    returns the smallest item in array by comparing them with >
*/
Array.prototype.min = function() {
    if (!this.length) return;
    var n = this[0];
    for (var i=1; i<this.length; i++) if (n>this[i]) n=this[i];
    return n;
}



/*  A.min()
    returns the graetest item in array by comparing them with <
*/
Array.prototype.max = function() {
    if (!this.length) return;
    var n = this[0];
    for (var i=1; i<this.length; i++) if (n<this[i]) n=this[i];
    return n;
}



/*  A.first()
    returns first element of Array
*/
Array.prototype.first = function() { return this[0] }



/*  A.last()
    returns last element of Array
*/
Array.prototype.last = function() { return this[this.length-1] }



/*  A.sjoin()
    Shorthand for A.join(' ')
*/
Array.prototype.sjoin = function() { return this.join(' ') }



/*  A.njoin()
    Shorthand for A.join('\n')
*/
Array.prototype.njoin = function() { return this.join('\n') }



/*  A.cjoin()
    Shorthand for A.join(', ')
*/
Array.prototype.cjoin = function() { return this.join(', ') }



/*  A.equals(a [, strict])
    true if all elements of array are equal to all elements of `a` in the same
    order. if strict is specified and true, all elements must be equal and of
    the same type.
*/
Array.prototype.equals = function(a, strict){
    if (this==a) return true;
    if (a.length != this.length) return false;
    return this.map(function(item,idx){
        return strict? item === a[idx] : item == a[idx]
    }).all();
}



/*  A.all([fn])
    Returns true if fn returns true for all elements in array
    if fn is not specified, returns true if all elements in array evaluate to
    true
*/
Array.prototype.all = function(fn) {
    return filter(this, fn).length == this.length;
}



/*  A.any([fn])
    Returns true if fn returns true for any elements in array
    if fn is not specified, returns true if at least one element in array 
    evaluates to true
*/
Array.prototype.any = function(fn) {
    return filter(this, fn).length > 0;
}



/*  A.each(fn)
    method form of each function
*/
Array.prototype.each = function(fn) { return each(this, fn) }



/*  A.map([fn])
    method form of map function
*/
Array.prototype.map = function(fn) { return map(this, fn) }



/*  A.filter([fn])
    method form of filter function
*/
Array.prototype.filter = function(fn) { return filter(this, fn) }


Array.prototype.select = Array.prototype.filter


/*  A.reduce([initial,] fn)
    method form of filter function
*/
Array.prototype.reduce = function() {
    var args = map(arguments);
    fn = args.pop();
    d  = args.pop();
    return reduce(this, d, fn); 
}


Array.prototype.inject = Array.prototype.reduce



/*  A.reject(fn)
    deletes items in A *in place* for which fn(item) is true
    returns a
*/
Array.prototype.reject = function(fn) {
    if (typeof(fn)=='string') fn = __strfn('item,idx,list', fn);
    var self = this;
    var itemsToRemove = [];
    fn = fn || function(v) {return v};
    map(self, function(item,idx,list) { if (fn(item,idx,list)) itemsToRemove.push(idx) } );
    itemsToRemove.reverse().each(function(idx) { self.splice(idx,1) });
    return self;
}



/*  __strfn(args, fn)
    this is used internally by each, map, combine, filter and reduce to accept
    strings as functions.

    takes:
        `args` -> a string of comma separated names of the function arguments
        `fn`   -> the function body

    if `fn` does not contain a return statement, a return keyword will be added
    before the last statement. the last statement is determined by removing the
    trailing semicolon (';') (if it exists) and then searching for the last
    semicolon, hence, caveats may apply (i.e. if the last statement has a
    string or regex containing the ';' character things will go wrong)
*/
function __strfn(args, fn) {
    function quote(s) { return '"' + s.replace(/"/g,'\\"') + '"' }
    if (!/\breturn\b/.test(fn)) {
        fn = fn.replace(/;\s*$/, '');
        fn = fn.insert(fn.lastIndexOf(';')+1, ' return ');
    }
    return eval('new Function('
        + map(args.split(/\s*,\s*/), quote).join()
        + ','
        + quote(fn)
        + ')'
        );
}



/*  each(list, fn)
    traverses `list`, applying `fn` to each item of `list`
    takes:
        `list` -> anything that can be indexed and has a `length` property.
                  usually an array.
        `fn`   -> either a function, or  a string containing a function body,
                  in which case the name of the paremeters passed to it will be
                  'item', 'idx' and 'list'.
                  se doc for `__strfn` for peculiarities about passing strings
                  for `fn`

    `each` provides a safe way for traversing only an array's indexed items,
    ignoring its other properties. (as opposed to how for-in works)
*/
function each(list, fn) {
    if (typeof(fn)=='string') return each(list, __strfn('item,idx,list', fn));
    for (var i=0; i < list.length; i++) fn(list[i], i, list);
}


/*  map(list [, fn])
    traverses `list`, applying `fn` to each item of `list`, returning an array
    of values returned by `fn`

    parameters work the same as for `each`, same `__strfn` caveats apply

    if `fn` is not provided, the list item is returned itself. this is an easy
    way to transform fake arrays (e.g. the arguments object of a function or
    nodeList objects) into real javascript arrays.
    e.g.: args = map(arguments)

    If you don't care about map's return value, you should use `each`

    this is a simplified version of python's map. parameter order is different,
    only a single list (array) is accepted, and the parameters passed to [fn]
    are different:
    [fn] takes the current item, then, optionally, the current index and a
    reference to the list (so that [fn] can modify list)
    see `combine` if you want to pass multiple lists
*/
function map(list, fn) {
    if (typeof(fn)=='string') return map(list, __strfn('item,idx,list', fn));

    var result = [];
    fn = fn || function(v) {return v};
    for (var i=0; i < list.length; i++) result.push(fn(list[i], i, list));
    return result;
}


/*  combine(list [, list]* [, fn])

    takes:
        `list`s -> one or more lists (see `each` for definition of a list)
        `fn`    -> Similar s `each` or `map`, a function or a string containing
                   a function body.
                   if a string is used, the name of parameters passed to the
                   created function will be the lowercase alphabet letters, in
                   order: a,b,c...
                   same `__strfn` caveats apply

    combine will traverse all lists concurrently, passing each row if items as
    parameters to `fn`
    if `fn` is not provided, a function that returns a list containing each
    item in the row is used.
    if a list is smaller than the other, `null` is used in place of its missing
    items

    returns:
        an array of the values returned by calling `fn` for each row of items
*/
function combine() {
    var args   = map(arguments);
    var lists  = map(args.slice(0,-1),'map(item)');
    var fn     = args.last();
    var toplen = map(lists, "item.length").max();
    var vals   = [];

    if (!fn) fn = function(){return map(arguments)};
    if (typeof fn == 'string') {
        if (lists.length > 26) throw 'string functions can take at most 26 lists';
        var a = 'a'.charCodeAt(0);
        fn = __strfn(map(range(a, a+lists.length),'String.fromCharCode(item)').join(','), fn);
    }

    map(lists, function(li) {
        while (li.length < toplen) li.push(null);
        map(li, function(item,ix){
            if (ix < vals.length) vals[ix].push(item);
            else vals.push([item]);
        });
    });

    return map(vals, function(val) { return fn.apply(fn, val) });
}



/*  filter(list [, fn])
    returns an array of items in `list` for which `fn(item)` is true

    parameters work the same as for `each`, same `__strfn` caveats apply

    if `fn` is not specified the items are evaluated themselves, that is,
    filter will return an array of the items in `list` which evaluate to true

    this is a similar to python's filter, but parameter order is inverted
*/
function filter(list, fn) {
    if (typeof(fn)=='string') return filter(list, __strfn('item,idx,list', fn));

    var result = [];
    fn = fn || function(v) {return v};
    map(list, function(item,idx,list) { if (fn(item,idx,list)) result.push(item) } );
    return result;
}



/*  reduce(list [, initial], fn)
    similar to python's reduce. paremeter onder inverted...

    TODO: document this properly

    takes:
        `list`   -> see doc for `each` to learn more about it
        `inirial -> TODO: doc`
        `fn`     -> similar to `each` too, but in the case where it's a string,
                    the name of the paremeters passed to it will be 'a' and 'b'
                    same `__strfn` caveats apply

*/
function reduce(list, initial, fn) {
    if (undef(fn)) {
        fn      = initial;
        initial = window.undefined; // explicit `window` object so browsers that do not have an `undefined` keyword will evaluate to the (hopefully) undefined parameter `undefined` of `window` 
    }
    if (typeof(fn)=='string') return reduce(list, initial, __strfn('a,b', fn));
    if (isdef(initial)) list.splice(0,0,initial);
    if (list.length===0) return false;
    if (list.length===1) return list[0];
    var result = list[0];
    var i = 1;
    while(i<list.length) result = fn(result,list[i++]);
    return result;
}

/*  range(start, stop, step)
    identical to python's range.
    range(stop)
    range(start,stop)
    range(start,stop,step)

    Return a list containing an arithmetic progression of integers.
    range(i, j) returns [i, i+1, i+2, ..., j-1]; start (!) defaults to 0.
    When step is given, it specifies the increment (or decrement).
    For example, range(4) returns [0, 1, 2, 3].  The end point is omitted!
    [from python's range's docstring]
*/
function range(start,stop,step) {
    if (isUndefined(stop)) return range(0,start,step);
    if (isUndefined(step)) step = 1;
    var ss = (step/Math.abs(step)); // step sign
    var r = [];
    for (i=start; i*ss<stop*ss; i=i+step) r.push(i);
    return r;
}