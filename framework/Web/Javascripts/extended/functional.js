/** 
FUNCTIONAL
by Caio Chassot (http://v2studio.com/k/code/)
*/

/** 
 * this is used internally by each, map, combine, filter and reduce to accept
 *   strings as functions.
 *
 *   if <b>fn</b> does not contain a return statement, a return keyword will be added
 *   before the last statement. the last statement is determined by removing the
 *   trailing semicolon (';') (if it exists) and then searching for the last
 *   semicolon, hence, caveats may apply (i.e. if the last statement has a
 *   string or regex containing the ';' character things will go wrong)
 * @param args a string of comma separated names of the function arguments
 * @param fn the function body
 */
function __strfn(args, fn) {
	/** 
	 * Internal function. Do not call it directly.
	 */
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


/** 
 * traverses <b>list</b>, applying <b>fn</b> to each item of <b>list</b>.
 * see doc for <b>__strfn</b> for peculiarities about passing strings for <b>fn</b>
 *
 * <b>each</b> provides a safe way for traversing only an array's indexed items,
 * ignoring its other properties. (as opposed to how for-in works)
 * @param list anything that can be indexed and has a <b>length</b> property. usually an array.
 * @param fn either a function, or  a string containing a function body,
 *           in which case the name of the paremeters passed to it will be
 *           'item', 'idx' and 'list'. 
 * @see #__strfn
 */
function each(list, fn) {
    if (typeof(fn)=='string') return each(list, __strfn('item,idx,list', fn));
    for (var i=0; i < list.length; i++) fn(list[i], i, list);
}


/** 
 * traverses <b>list</b>, applying <b>fn</b> to each item of <b>list</b>, returning an array
    of values returned by <b>fn</b>

    parameters work the same as for <b>each</b>, same <b>__strfn</b> caveats apply

    if <b>fn</b> is not provided, the list item is returned itself. this is an easy
    way to transform fake arrays (e.g. the arguments object of a function or
    nodeList objects) into real javascript arrays.
    e.g.: args = map(arguments)

    If you don't care about map's return value, you should use <b>each</b>

    this is a simplified version of python's map. parameter order is different,
    only a single list (array) is accepted, and the parameters passed to [fn]
    are different:
    [fn] takes the current item, then, optionally, the current index and a
    reference to the list (so that [fn] can modify list)
    see <b>combine</b> if you want to pass multiple lists
 */
function map(list, fn) {
    if (typeof(fn)=='string') return map(list, __strfn('item,idx,list', fn));

    var result = [];
    fn = fn || function(v) {return v};
    for (var i=0; i < list.length; i++) result.push(fn(list[i], i, list));
    return result;
}


/** 
 *  combine will traverse all lists concurrently, passing each row if items as
    parameters to <b>fn</b>
    if <b>fn</b> is not provided, a function that returns a list containing each
    item in the row is used.
    if a list is smaller than the other, <b>null</b> is used in place of its missing
    items
 * @param list one or more lists (see <b>each</b> for definition of a list)
 * @param fn Similar s <b>each</b> or <b>map</b>, a function or a string containing
                   a function body.
                   if a string is used, the name of parameters passed to the
                   created function will be the lowercase alphabet letters, in
                   order: a,b,c...
                   same <b>__strfn</b> caveats apply
 * @see #each
 * @see #__strfn
 * @return an array of the values returned by calling <b>fn</b> for each row of items
 *//*
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

/** 
 *  returns an array of items in <b>list</b> for which <b>fn(item)</b> is true

    parameters work the same as for <b>each</b>, same <b>__strfn</b> caveats apply

    if <b>fn</b> is not specified the items are evaluated themselves, that is,
    filter will return an array of the items in <b>list</b> which evaluate to true

    this is a similar to python's filter, but parameter order is inverted
 *//*
function filter(list, fn) {
    if (typeof(fn)=='string') return filter(list, __strfn('item,idx,list', fn));

    var result = [];
    fn = fn || function(v) {return v};
    map(list, function(item,idx,list) { if (fn(item,idx,list)) result.push(item) } );
    return result;
}

/** 
 * similar to python's reduce. paremeter order inverted...
 * @param list see doc for <b>each</b> to learn more about it
 * @param initial TODO
 * @param fn similar to <b>each</b> too, but in the case where it's a string,
                    the name of the paremeters passed to it will be 'a' and 'b'
                    same <b>__strfn</b> caveats apply
 *//*
function reduce(list, initial, fn) {
    if (undef(fn)) {
        fn      = initial;
		// explicit <b>window</b> object so browsers that do not have an <b>undefined</b> 
		//keyword will evaluate to the (hopefully) undefined parameter 
		//<b>undefined</b> of <b>window</b> 
        initial = window.undefined; 
    }
    if (typeof(fn)=='string') return reduce(list, initial, __strfn('a,b', fn));
    if (isdef(initial)) list.splice(0,0,initial);
    if (list.length===0) return false;
    if (list.length===1) return list[0];
    var result = list[0];
    var i = 1;
    while(i<list.length) result = fn(result,list[i++]);
    return result;
}*/

