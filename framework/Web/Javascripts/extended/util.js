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
function isList(o) { return o && isObject(o) && (isArray(o) || o.item) }


if(!Prado) var Prado = {};

Prado.Util = {}

/**
 * Pad a number with zeros from the left.
 * @param integer number
 * @param integer total string length
 * @return string zero  padded number
 */
Prado.Util.pad = function(number, X)
{
		X = (!X ? 2 : X);
		number = ""+number;
		while (number.length < X)
			number = "0" + number;
		return number;
}

/** 
 * Convert a string into integer, returns null if not integer.
 * @param {string} the string to convert to integer
 * @type {integer|null} null if string does not represent an integer.
 */
Prado.Util.toInteger = function(value)
{
	var exp = /^\s*[-\+]?\d+\s*$/;
	if (value.match(exp) == null)
		return null;
	var num = parseInt(value, 10);
	return (isNaN(num) ? null : num);
}

/** 
 * Convert a string into a double/float value. <b>Internationalization 
 * is not supported</b>
 * @param {string} the string to convert to double/float
 * @param {string} the decimal character
 * @return {float|null} null if string does not represent a float value
 */
Prado.Util.toDouble = function(value, decimalchar)
{
	decimalchar = undef(decimalchar) ? "." : decimalchar;
	var exp = new RegExp("^\\s*([-\\+])?(\\d+)?(\\" + decimalchar + "(\\d+))?\\s*$");
    var m = value.match(exp);
    if (m == null)	
		return null;
	var cleanInput = m[1] + (m[2].length>0 ? m[2] : "0") + "." + m[4];
    var num = parseFloat(cleanInput);
    return (isNaN(num) ? null : num);
}

/**
 * Convert strings that represent a currency value (e.g. a float with grouping 
 * characters) to float. E.g. "10,000.50" will become "10000.50". The number 
 * of dicimal digits, grouping and decimal characters can be specified.
 * <i>The currency input format is <b>very</b> strict, null will be returned if
 * the pattern does not match</i>.
 * @param {string} the currency value
 * @param {string} the grouping character, default is ","
 * @param {int} number of decimal digits
 * @param {string} the decimal character, default is "."
 * @type {float|null} the currency value as float.
 */
Prado.Util.toCurrency = function(value, groupchar, digits, decimalchar)
{
	groupchar = undef(groupchar) ? "," : groupchar;
	decimalchar = undef(decimalchar) ? "." : decimalchar;
	digits = undef(digits) ? 2 : digits;

	var exp = new RegExp("^\\s*([-\\+])?(((\\d+)\\" + groupchar + ")*)(\\d+)"
		+ ((digits > 0) ? "(\\" + decimalchar + "(\\d{1," + digits + "}))?" : "")
        + "\\s*$");
	var m = value.match(exp);
	if (m == null)
		return null;
	var intermed = m[2] + m[5] ;
    var cleanInput = m[1] + intermed.replace(
			new RegExp("(\\" + groupchar + ")", "g"), "") 
							+ ((digits > 0) ? "." + m[7] : "");
	var num = parseFloat(cleanInput);
	return (isNaN(num) ? null : num);
}

/**
 * Trim the value, if the value is undefined, empty string is return.
 * @param {string} string to be trimmed.
 * @type {string} trimmed string.
 */
Prado.Util.trim = function(value)
{
	if(!isString(value)) return "";
	return value.replace(/^\s+|\s+$/g, "");
}
