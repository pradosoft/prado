
function runNextTest() {
    if (!runAllTests)
            return;

    suiteTable = getIframeDocument(getSuiteFrame()).getElementsByTagName("table")[0];

    // Do not change the row color of the first row
    if (currentRowInSuite > 0) {
        // Provide test-status feedback
        if (testFailed) {
            setCellColor(suiteTable.rows, currentRowInSuite, 0, failColor);
        } else {
            setCellColor(suiteTable.rows, currentRowInSuite, 0, passColor);
        }

        // Set the results from the previous test run
        setResultsData(suiteTable, currentRowInSuite);
    }

    currentRowInSuite++;

    // If we are done with all of the tests, set the title bar as pass or fail
    if (currentRowInSuite >= suiteTable.rows.length) {
        if (suiteFailed) {
            setCellColor(suiteTable.rows, 0, 0, failColor);
        } else {
            setCellColor(suiteTable.rows, 0, 0, passColor);
        }

        LOG.warn("next? ", "warn");
        // If this is an automated run (i.e., build script), then submit
        // the test results by posting to a form

        postTestResults(suiteFailed, suiteTable);
    }

    else {
        // Make the current row blue
        setCellColor(suiteTable.rows, currentRowInSuite, 0, workingColor);

        testLink = suiteTable.rows[currentRowInSuite].cells[0].getElementsByTagName("a")[0];
        testLink.focus();

        var testFrame = getTestFrame();
        addLoadListener(testFrame, startTest);

        selenium.browserbot.setIFrameLocation(testFrame, testLink.href);
    }
}

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
function postTestResults(suiteFailed, suiteTable) {

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

    form.createHiddenField("totalTime", Math.floor((currentTime - startTime) / 1000));
    form.createHiddenField("numTestPasses", numTestPasses);
    form.createHiddenField("numTestFailures", numTestFailures);
    form.createHiddenField("numCommandPasses", numCommandPasses);
    form.createHiddenField("numCommandFailures", numCommandFailures);
    form.createHiddenField("numCommandErrors", numCommandErrors);

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
	var color = element.getAttribute("bgcolor");
	if(color == passColor) return "passed";
	if(color == failColor) return "failed";
	if(color == doneColor) return "done";
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