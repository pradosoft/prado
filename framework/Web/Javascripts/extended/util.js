/** 
 * Test if it is an object and has no constructors.
 */
function isAlien(a)     { return isObject(a) && typeof a.constructor != 'function' }

/** 
 * isArray?
 */
function isArray(a)     { return isObject(a) && a.constructor == Array }

/** 
 * isBoolean?
 */
function isBoolean(a)   { return typeof a == 'boolean' }

/** 
 * isFunction?
 */
function isFunction(a)  { return typeof a == 'function' }

/** 
 * isNull?
 */
function isNull(a)      { return typeof a == 'object' && !a }

/** 
 * isNumber?
 */
function isNumber(a)    { return typeof a == 'number' && isFinite(a) }

/** 
 * isObject?
 */
function isObject(a)    { return (a && typeof a == 'object') || isFunction(a) }

/** 
 * isRegexp?
 * we would prefer to use instanceof, but IE/mac is crippled and will choke at it
 */
function isRegexp(a)    { return a && a.constructor == RegExp }

/** 
 * isString?
 */
function isString(a)    { return typeof a == 'string' }

/** 
 * isUndefined?
 */
function isUndefined(a) { return typeof a == 'undefined' }

/** 
 * isEmpty?
 */
function isEmpty(o)     {
    var i, v;
    if (isObject(o)) {
        for (i in o) {
            v = o[i];
            if (isUndefined(v) && isFunction(v)) {
                return false;
            }
        }
    }
    return true;
}

/** 
 * alias for isUndefined
 */
function undef(v) { return  isUndefined(v) }

/** 
 * alias for !isUndefined
 */
function isdef(v) { return !isUndefined(v) }

/** 
 * true if o is an Element Node or document or window. The last two because it's used for onload events
    if you specify strict as true, return false for document or window
 */
function isElement(o, strict) {
    return o && isObject(o) && ((!strict && (o==window || o==document)) || o.nodeType == 1)
}

/** 
 * true if o is an Array or a NodeList, (NodeList in Opera returns a type of function)
 */
function isList(o) { return o && isObject(o) && (isArray(o) || (o.item && o.tagName.toLowerCase() != "select")) }


