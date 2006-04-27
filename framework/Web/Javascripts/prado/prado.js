
var Prado = 
{ 
	Version: '3.0',
	
	/**
	 * Returns browser information. Example
	 * <code>
	 * var browser = Prado.Browser();
	 * alert(browser.ie); //should ouput true if IE, false otherwise
	 * </code>
	 * @param ${parameter}
	 * @return ${return}
	 */
	Browser : function()
	{
		var info = { Version : "1.0" };
		var is_major = parseInt( navigator.appVersion );
		info.nver = is_major;
		info.ver = navigator.appVersion;
		info.agent = navigator.userAgent;
		info.dom = document.getElementById ? 1 : 0;
		info.opera = window.opera ? 1 : 0;
		info.ie5 = ( info.ver.indexOf( "MSIE 5" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie6 = ( info.ver.indexOf( "MSIE 6" ) > -1 && info.dom && !info.opera ) ? 1 : 0;
		info.ie4 = ( document.all && !info.dom && !info.opera ) ? 1 : 0;
		info.ie = info.ie4 || info.ie5 || info.ie6;
		info.mac = info.agent.indexOf( "Mac" ) > -1;
		info.ns6 = ( info.dom && parseInt( info.ver ) >= 5 ) ? 1 : 0;
		info.ie3 = ( info.ver.indexOf( "MSIE" ) && ( is_major < 4 ) );
		info.hotjava = ( info.agent.toLowerCase().indexOf( 'hotjava' ) != -1 ) ? 1 : 0;
		info.ns4 = ( document.layers && !info.dom && !info.hotjava ) ? 1 : 0;
		info.bw = ( info.ie6 || info.ie5 || info.ie4 || info.ns4 || info.ns6 || info.opera );
		info.ver3 = ( info.hotjava || info.ie3 );
		info.opera7 = ( ( info.agent.toLowerCase().indexOf( 'opera 7' ) > -1 ) || ( info.agent.toLowerCase().indexOf( 'opera/7' ) > -1 ) );
		info.operaOld = info.opera && !info.opera7;
		return info;
	},
	
	ImportCss : function(doc, css_file) 
	{
		if (Prado.Browser().ie)
			var styleSheet = doc.createStyleSheet(css_file);
		else 
		{
			var elm = doc.createElement("link");

			elm.rel = "stylesheet";
			elm.href = css_file;

			if (headArr = doc.getElementsByTagName("head"))
				headArr[0].appendChild(elm);
		}
	}	
};
