
objectExtend(HtmlTestRunnerControlPanel.prototype, {
	getTestSuiteName: function() {
        return document.location+'?testSuites'; //this._getQueryParameter("test");
    }
});

SeleniumFrame.prototype._setLocation = function(location) {
       /* var isChrome = browserVersion.isChrome || false;
        var isHTA = browserVersion.isHTA || false;
        // DGF TODO multiWindow
        location += "?thisIsChrome=" + isChrome + "&thisIsHTA=" + isHTA;*/
        if (browserVersion.isSafari) {
            // safari doesn't reload the page when the location equals to current location.
            // hence, set the location to blank so that the page will reload automatically.
            this.frame.src = "about:blank";
            this.frame.src = location;
        } else {
            this.frame.contentWindow.location.replace(location);
        }
    };

SeleniumFrame.prototype._attachStylesheet = function()
{
	    var base_url = script_base_url;
        var d = this.getDocument();
        var head = d.getElementsByTagName('head').item(0);
        var styleLink = d.createElement("link");
        styleLink.rel = "stylesheet";
        styleLink.type = "text/css";
         styleLink.href = base_url + "core/selenium-test.css";
        head.appendChild(styleLink);
};

HtmlTestFrame.prototype._setLocation = SeleniumFrame.prototype._setLocation;
HtmlTestSuiteFrame.prototype._setLocation = SeleniumFrame.prototype._setLocation;

HtmlTestFrame.prototype._attachStylesheet = SeleniumFrame.prototype._attachStylesheet;
HtmlTestSuiteFrame.prototype._attachStylesheet = SeleniumFrame.prototype._attachStylesheet;


objectExtend(HtmlTestRunnerControlPanel.prototype, {
    _parseQueryParameter: function() {
        var tempRunInterval = this._getQueryParameter("runInterval");
        if (tempRunInterval) {
            this.setRunInterval(tempRunInterval);
        }
    }
});



/**
 * Override selenium implementation.
 */
Selenium.prototype.getAttribute = function(target) {
    return this.page().findAttribute(target);
};


/**
 * Override selenium implementation.
 */
Selenium.prototype.isVisible = function(locator) {
    var element;
    element = this.page().findElement(locator);

	if(/Konqueror|Safari|KHTML/.test(navigator.userAgent))
		var visibility = element.style["visibility"];
	else
    	var visibility = this.findEffectiveStyleProperty(element, "visibility");

   	var _isDisplayed = this._isDisplayed(element);
    return (visibility != "hidden" && _isDisplayed);
};


/**
 * Override selenium implementation.
 */
Selenium.prototype._isDisplayed = function(element) {
    if(/Konqueror|Safari|KHTML/.test(navigator.userAgent))
		var display = element.style["display"];
	else
		var display = this.findEffectiveStyleProperty(element, "display");
    if (display == "none") return false;
    if (element.parentNode.style) {
        return this._isDisplayed(element.parentNode);
    }
    return true;
};

Selenium.prototype.assertEmptySelection = function(selectLocator, optionLocator)
{
	/**
   * Verifies that the selected option of a drop-down satisfies the optionSpecifier.
   *
   * <p>See the select command for more information about option locators.</p>
   *
   * @param selectLocator an <a href="#locators">element locator</a> identifying a drop-down menu
   * @param optionLocator an option locator, typically just an option label (e.g. "John Smith")
   */
    var element = this.page().findElement(selectLocator);
    var locator = this.optionLocatorFactory.fromLocatorString(optionLocator);
   return element.selectedIndex == -1;
}


objectExtend(HtmlTestSuite.prototype, {
	_onTestSuiteComplete: function() {
        this.markDone();
        var result = new TestResult(this.failed, this.getTestTable());
		postTestResults(this.failed, this.getTestTable(), result);
   }
});




// Post the results to a servlet, CGI-script, etc.  The URL of the
// results-handler defaults to "/postResults", but an alternative location
// can be specified by providing a "resultsUrl" query parameter.
//
// Parameters passed to the results-handler are:
//      result:         passed/failed depending on whether the suite passed or failed
//      totalTime:      the total running time in seconds for the suite.
//
//      numTestPasses:  the total number of tests which passed.
//      numTestFailures: the total number of tests which failed.
//
//      numCommandPasses: the total number of commands which passed.
//      numCommandFailures: the total number of commands which failed.
//      numCommandErrors: the total number of commands which errored.
//
//      suite:      the suite table, including the hidden column of test results
//      testTable.1 to testTable.N: the individual test tables
//
function postTestResults(suiteFailed, suiteTable, result) {

    form = document.createElement("form");
    document.body.appendChild(form);

    form.id = "resultsForm";
    form.method="post";
    form.target="myiframe";

    var resultsUrl = post_results_to;
    if (!resultsUrl) {
        resultsUrl = "./results.php";
    }

    var actionAndParameters = resultsUrl.split('?',2);
    form.action = actionAndParameters[0];
    LOG.warn(form.action)
    var resultsUrlQueryString = actionAndParameters[1];

    form.createHiddenField = function(name, value) {
        input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = value;
        this.appendChild(input);
    };

    if (resultsUrlQueryString) {
        var clauses = resultsUrlQueryString.split('&');
        for (var i = 0; i < clauses.length; i++) {
            var keyValuePair = clauses[i].split('=',2);
            var key = unescape(keyValuePair[0]);
            var value = unescape(keyValuePair[1]);
            form.createHiddenField(key, value);
        }
    }

    form.createHiddenField("result", suiteFailed == true ? "failed" : "passed");
	form.createHiddenField("totalTime", Math.floor((result.metrics.currentTime - result.metrics.startTime) / 1000));
	form.createHiddenField("numTestPasses", result.metrics.numTestPasses);
	form.createHiddenField("numTestFailures", result.metrics.numTestFailures);
	form.createHiddenField("numCommandPasses", result.metrics.numCommandPasses);
	form.createHiddenField("numCommandFailures", result.metrics.numCommandFailures);
	form.createHiddenField("numCommandErrors", result.metrics.numCommandErrors);

    // Create an input for each test table.  The inputs are named
    // testTable.1, testTable.2, etc.
    for (rowNum = 1; rowNum < suiteTable.rows.length;rowNum++) {
        // If there is a second column, then add a new input
        if (suiteTable.rows[rowNum].cells.length > 1) {
            var resultCell = suiteTable.rows[rowNum].cells[1];
            parse_resultCell(resultCell,rowNum,form);
            //form.createHiddenField("tests[]", resultCell.innerHTML);
            // remove the resultCell, so it's not included in the suite HTML
            //resultCell.parentNode.removeChild(resultCell);
        }
    }

    // Add HTML for the suite itself
    //form.createHiddenField("suite", suiteTable.parentNode.innerHTML);

    form.submit();
    document.body.removeChild(form);
}

function parse_resultCell(resultCell,rowNum,form)
{
	var div = resultCell.childNodes[0];
	var table;
	for(var i = 0; i<div.childNodes.length; i++)
	{
		if(div.childNodes[i].nodeName.toLowerCase() == 'table')
			table = div.childNodes[i];
	}
	//LOG.info(div.innerHTML);
	var testname = table.rows[0].cells[0].firstChild.innerHTML;
	var resultColor = get_color_status(table.rows[0]);

	form.createHiddenField("tests["+rowNum+"][testcase]",testname);

	//var trace = window.testSuiteFrame.prado_trace[testname];

	for(var i = 1; i<table.rows.length; i++)
	{
		var msg = table.rows[i].getAttribute("title");
		var result = get_color_status(table.rows[i]);
		var action = table.rows[i].cells[0].innerHTML;
		var target = table.rows[i].cells[1].innerHTML;
		var param = table.rows[i].cells[2].innerHTML;
		var id = "tests["+rowNum+"][commands]["+(i-1)+"]";
		form.createHiddenField(id+"[command]", "|"+action+"|"+target+"|"+param+"|");
		form.createHiddenField(id+"[result]", result);
		form.createHiddenField(id+"[msg]", msg);
		//form.createHiddenField(id+"[trace]", trace[i-1]);
	}
}

function get_color_status(element)
{
	var color = element.className
	if(color == 'status_passed') return "passed";
	if(color == 'status_failed') return "failed";
	if(color == 'status_done') return "done";
	return "";
}




Selenium.prototype.assertHTMLPresent = function(expectedValue) {
    var actualValue = this.page().currentDocument.body.innerHTML;
   if(actualValue.indexOf(expectedValue) >= 0)
	   return;
   Assert.fail("Unable to find '"+(expectedValue.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "\""))+"' in document.body");
};

Selenium.prototype.assertHTMLNotPresent = function(expectedValue) {
    var actualValue = this.page().currentDocument.body.innerHTML;
   if(actualValue.indexOf(expectedValue) < 0)
	   return;
   Assert.fail("'"+(expectedValue.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "\""))+"' was found in document.body");
};