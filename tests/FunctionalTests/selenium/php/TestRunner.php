<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
<HTA:APPLICATION ID="SeleniumTestRunner" APPLICATIONNAME="Selenium" >
<!-- the previous line is only relevant if you rename this
     file to "TestRunner.hta" -->

<!-- The copyright notice and other comments have been moved to after the HTA declaration,
     to work-around a bug in IE on Win2K whereby the HTA application doesn't function correctly -->
<!--
Copyright 2004 ThoughtWorks, Inc

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
-->
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type" />

<title>Prado Functional Test Runner</title>
<link rel="stylesheet" type="text/css" href="<?php echo $base_dir; ?>selenium.css" />
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>html-xpath/html-xpath-patched.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-browserbot.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-api.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-commandhandlers.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-executionloop.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-testrunner.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>selenium-logging.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>htmlutils.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>xpath.js"></script>
<script language="JavaScript" type="text/javascript" src="<?php echo $base_dir; ?>prado-functional-test.js"></script>
<script language="JavaScript" type="text/javascript">
    function openDomViewer() {
        var autFrame = document.getElementById('myiframe');
        var autFrameDocument = getIframeDocument(autFrame);
        this.rootDocument = autFrameDocument;
        var domViewer = window.open('<?php echo $base_dir; ?>domviewer.html');
        return false;
    }

  	Logger.prototype.openLogWindow = function() {
        this.logWindow = window.open(
            "<?php echo $base_dir; ?>SeleniumLog.html", "SeleniumLog",
            "width=600,height=250,bottom=0,right=0,status,scrollbars,resizable"
        );
        return this.logWindow;
    };

    var post_results_to = "<?php echo $driver; ?>";
</script>
</head>

<body onload="start();">

    <table class="layout">
    <form action="" name="controlPanel">

      <!-- Suite, Test, Control Panel -->

      <tr class="selenium">
        <td width="25%" height="30%" rowspan="2"><iframe name="testSuiteFrame" id="testSuiteFrame" src="<?php echo $driver; ?>?testSuites"></iframe></td>
        <td width="50%" height="30%" rowspan="2"><iframe name="testFrame" id="testFrame"></iframe></td>
        <th width="25%" height="1" class="header">
          <h1><a href="http://selenium.thoughtworks.com" title="The Selenium Project">Selenium</a> TestRunner</h1>
        </th>
      </tr>

      <tr class="selenium">
        <td width="25%" height="30%" id="controlPanel">

          <fieldset>
            <legend>Execute Tests</legend>

            <div>
              <input id="modeRun" type="radio" name="runMode" value="0" checked="checked"/><label for="modeRun">Run</label>
              <input id="modeWalk" type="radio" name="runMode" value="500" /><label for="modeWalk">Walk</label>
              <input id="modeStep" type="radio" name="runMode" value="-1" /><label for="modeStep">Step</label>
            </div>

            <div>
              <button type="button" id="runSuite" onclick="startTestSuite();"
                      title="Run the entire Test-Suite">
                <strong>All</strong>
              </button>
              <button type="button" id="runTest" onclick="runSingleTest();"
                      title="Run the current Test">
                <em>Selected</em>
              </button>
              <button type="button" id="continueTest" disabled="disabled"
                      title="Continue the Test">
                Continue
              </button>
            </div>

          </fieldset>

          <table id="stats" align="center">
            <tr>
              <td colspan="2" align="right">Elapsed:</td>
              <td id="elapsedTime" colspan="2">00.00</td>
            </tr>
            <tr>
              <th colspan="2">Tests</th>
              <th colspan="2">Commands</th>
            </tr>
            <tr>
              <td class="count" id="testRuns">0</td>
              <td>run</td>
              <td class="count" id="commandPasses">0</td>
              <td>passed</td>
            </tr>
            <tr>
              <td class="count" id="testFailures">0</td>
              <td>failed</td>
              <td class="count" id="commandFailures">0</td>
              <td>failed</td>
            </tr>
            <tr>
              <td colspan="2"></td>
              <td class="count" id="commandErrors">0</td>
              <td>incomplete</td>
            </tr>
          </table>

          <fieldset>
            <legend>Tools</legend>

            <button type="button" id="domViewer1" onclick="openDomViewer();">
              View DOM
            </button>
            <button type="button" onclick="LOG.show();">
              Show Log
            </button>

          </fieldset>

        </td>
      </tr>

      <!-- AUT -->

      <tr>
        <td colspan="3" height="70%"><iframe name="myiframe" id="myiframe" src="<?php echo $base_dir; ?>TestRunner-splash.html"></iframe></td>
      </tr>
    </form>
    </table>

</body>
</html>
