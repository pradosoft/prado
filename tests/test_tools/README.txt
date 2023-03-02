== PRADO Functional Tests ==

Functional tests are browser based that tests the overall functional of a Prado application. The tests can be written in PHP, see "framework/..." within this directory to see some examples. To run the tests, open your browser to "../tests/FunctionalTests/index.php" and click on the "All" button.


=== Writing Tests ===
Lets test some part of a Prado application. Create a new php file, e.g.

testExample1.php

<php>
<?php
class testExample1 extends SeleniumTestCase
{
	function setup()
	{
		$this->open('../examples/myexample/index.php');
	}

	function testButtonClickExample()
	{
		//using xpath to find the button with value "Click Me!"
		$this->click('//input[@value="Click Me!"]');

		//..more commands and assertions
	}
}
</php>

=== Tests as part of Example code ===
Tests can also be place within the example page, e.g. suppose we have an example call MyButtonExample.

File: MyButtonExample.php
<php>
<?php
//Example class, changes the Text of a button when clicked.
class MyButtonExample extends TPage
{
	function button_clicked($sender, $param)
	{
		$sender->Text = "Hello World!";
	}
}


class testMyButtonExample extends SeleniumTestCase
{
	function setup()
	{
		//get the test page url
		$page = Prado::getApplication()->getTestPage(__FILE__);

		//open MyButtonExample page
		$this->open($page);
	}

	function testButtonClick()
	{
		$this->assertTextPresent("My Button Example");
		$this->click('//input[@value="Click Me!"]');
		$this->click('//input[@value="Hello World!"]');
	}
}
</php>

File: MyButtonExample.tpl
<prado>
<com:TForm>
	<h1>My Button Example</h1>
	<com:TButton Text="Click Me!"
		Click="button_clicked" />
</com:TForm>
</prado>

== Selenium Reference ==

A '''command''' is what tells Selenium what to do. Selenium commands come in two 'flavors', '''Actions''' and '''Assertions'''. Each command call has the following syntax
<php>
	$this->[command]([target], [value]);
</php>
Note that some commands does not need a [value].

'''Actions''' are commands that generally manipulate the state of the application. They do things like "click this link" and "select that option". If an Action fails, or has an error, the execution of the current test is stopped.


'''Checks''' verify the state of the application conforms to what is expected. Examples include "make sure the page title is X" and "check that this checkbox is checked". It is possible to tell Selenium to stop the test when an Assertion fails, or to simply record the failure and continue.

'''Element Locators''' tell Selenium which HTML element a command refers to. Many commands require an Element Locator as the "target" attribute. Examples of Element Locators include "elementId" and "document.forms[0].element". These are described more clearly in the next section.

'''Patterns''' are used for various reasons, e.g. to specify the expected value of an input field, or identify a select option. Selenium supports various types of pattern, including regular-expressions, all of which are described in more detail below.

=== Element Locators ===

Element Locators allow Selenium to identify which HTML element a command refers to.  Selenium support the following strategies for locating elements:

==== '''id='''''id'' ====
Select the element with the specified @id attribute.

==== '''name='''''name'' ====
Select the first element with the specified @name attribute.

==== '''identifier='''''id''====
Select the element with the specified @id attribute.  If no match is found, select the first element whose @name attribute is ''id''.

==== '''dom='''''javascriptExpression''====
Find an element using JavaScript traversal of the HTML Document Object Model. DOM locators ''must'' begin with "document.".
* dom=document.forms['myForm'].myDropdown
* dom=document.images[56]

==== '''xpath='''''xpathExpression''====
Locate an element using an XPath expression. XPath locators ''must'' begin with "//".
* xpath=//img[@alt='The image alt text']
* xpath=//table[@id='table1']//tr[4]/td[2]

==== '''link='''''textPattern'' ====
Select the link (anchor) element which contains text matching the specified ''pattern''.
* link=The link text

Without a locator prefix, Selenium uses:

* dom, for locators starting with "document."
* xpath, for locators starting with "//"
* identifier, otherwise


=== Select Option Specifiers ===

Select Option Specifiers provide different ways of specifying options of an HTML Select element (e.g. for selecting a specific option, or for asserting that the selected option satisfies a specification). There are several forms of Select Option Specifier.

==== label=labelPattern ====
matches options based on their labels, i.e. the visible text.
* label=regexp:^[Oo]ther

==== value=valuePattern ====
matches options based on their values.
* value=other

==== id=id ====
matches options based on their ids.
* id=option1

==== index=index ====
matches an option based on its index (offset from zero).
* index=2

Without a prefix, the default behaviour is to only match on labels.

String-match Patterns

    Various Pattern syntaxes are available for matching string values:

    glob:pattern
        Match a string against a "glob" (aka "wildmat") pattern. "Glob" is a kind of limited regular-expression syntax typically used in command-line shells. In a glob pattern, "*" represents any sequence of characters, and "?" represents any single character. Glob patterns match against the entire string.
    regexp:regexp
        Match a string using a regular-expression. The full power of JavaScript regular-expressions is available.
    exact:string
        Match a string exactly, verbatim, without any of that fancy wildcard stuff.

    If no pattern prefix is specified, Selenium assumes that it's a "glob" pattern.

Selenium Actions

    Actions tell Selenium to do something in the application. They generally represent something a user would do.

    Many Actions can be called with the "AndWait" suffix. This suffix tells Selenium that the action will cause the browser to make a call to the server, and that Selenium should wait for a new page to load. The exceptions to this pattern are the "open" and "click" actions, which will both wait for a page to load by default.

    open( url )

        Opens a URL in the test frame. This accepts both relative and absolute URLs.

        Note: The URL must be on the same site as Selenium due to security restrictions in the browser (Cross Site Scripting).

        examples:

            open 	/mypage
            open 	http://localhost/

    click( elementLocator )

        Clicks on a link, button, checkbox or radio button. If the click action causes a new page to load (like a link usually does), use "clickAndWait".

        examples:

            click 	aCheckbox
            clickAndWait 	submitButton
            clickAndWait 	anyLink

        note:
            Selenium will always automatically click on a popup dialog raised by the alert() or confirm() methods. (The exception is those raised during 'onload', which are not yet handled by Selenium). You must use [verify|assert]Alert or [verify|assert]Confirmation to tell Selenium that you expect the popup dialog. You may use chooseCancelOnNextConfirmation to click 'cancel' on the next confirmation dialog instead of clicking 'OK'.

    type( inputLocator, value )

        Sets the value of an input field, as though you typed it in.

        Can also be used to set the value of combo boxes, check boxes, etc. In these cases, value should be the value of the option selected, not the visible text.

        examples:

            type 	nameField 	John Smith
            typeAndWait 	textBoxThatSubmitsOnChange 	newValue

    select( dropDownLocator, optionSpecifier )

        Select an option from a drop-down, based on the optionSpecifier. If more than one option matches the specifier (e.g. due to the use of globs like "f*b*", or due to more than one option having the same label or value), then the first matches is selected.

        examples:

            select 	dropDown 	Australian Dollars
            select 	dropDown 	index=0
            selectAndWait 	currencySelector 	value=AUD
            selectAndWait 	currencySelector 	label=Aus*lian D*rs

    selectWindow( windowId )

        Selects a popup window. Once a popup window has been selected, all commands go to that window. To select the main window again, use "null" as the target.

        target: The id of the window to select.

        value: ignored

        examples:

            selectWindow 	myPopupWindow
            selectWindow 	null

    goBack()

        Simulates the user clicking the "back" button on their browser.

        examples:

            goBack

    close()

        Simulates the user clicking the "close" button in the titlebar of a popup window.

        examples:

            close

    pause( milliseconds )

        Pauses the execution of the test script for a specified amount of time. This is useful for debugging a script or pausing to wait for some server side action.

        examples:

            pause 	5000
            pause 	2000

    fireEvent( elementLocator, eventName )

        Explicitly simulate an event, to trigger the corresponding "onevent" handler.

        examples:

            fireEvent 	textField 	focus
            fireEvent 	dropDown 	blur

    waitForValue( inputLocator, value )

        Waits for a specified input (e.g. a hidden field) to have a specified value. Will succeed immediately if the input already has the value. This is implemented by polling for the value. Warning: can block indefinitely if the input never has the specified value.

        example:

            waitForValue 	finishIndication 	isfinished

    store( valueToStore, variableName )

        Stores a value into a variable. The value can be constructed using either variable substitution or javascript evaluation, as detailed in 'Parameter construction and Variables' (below).

        examples:

            store 	Mr John Smith 	fullname
            store 	${title} ${firstname} ${surname} 	fullname
            store 	javascript{Math.round(Math.PI * 100) / 100} 	PI

    storeValue( inputLocator, variableName )

        Stores the value of an input field into a variable.

        examples:

            storeValue 	userName 	userID
            type 	userName 	${userID}

    storeText( elementLocator, variableName )

        Stores the text of an element into a variable.

        examples:

            storeText 	currentDate 	expectedStartDate
            verifyValue 	startDate 	${expectedStartDate}

    storeAttribute( elementLocator@attributeName, variableName )

        Stores the value of an element attribute into a variable.

        examples:

            storeAttribute 	input1@class 	classOfInput1
            verifyAttribute 	input2@class 	${classOfInput1}

    chooseCancelOnNextConfirmation()

        Instructs Selenium to click Cancel on the next javascript confirmation dialog to be raised. By default, the confirm function will return true, having the same effect as manually clicking OK. After running this command, the next confirmation will behave as if the user had clicked Cancel.

        examples:

            chooseCancelOnNextConfirmation

    answerOnNextPrompt( answerString )

        Instructs Selenium to return the specified answerString in response to the next prompt.

        examples:

            answerOnNextPrompt 	Kangaroo

Selenium Checks

    Checks are used to verify the state of the application. They can be used to check the value of a form field, the presense of some text, or the URL of the current page.

    All Selenium Checks can be used in 2 modes, "assert" and "verify". These behave identically, except that when an "assert" check fails, the test is aborted. When a "verify" check fails, the test will continue execution. This allows a single "assert" to ensure that the application is on the correct page, followed by a bunch of "verify" checks to test form field values, labels, etc.

    assertLocation( relativeLocation )

        examples:

            verifyLocation 	/mypage
            assertLocation 	/mypage

    assertTitle( titlePattern )

        Verifies the title of the current page.

        examples:

            verifyTitle 	My Page
            assertTitle 	My Page

    assertValue( inputLocator, valuePattern )

        Verifies the value of an input field (or anything else with a value parameter). For checkbox/radio elements, the value will be "on" or "off" depending on whether the element is checked or not.

        examples:

            verifyValue 	nameField 	John Smith
            assertValue 	document.forms[2].nameField 	John Smith

    assertSelected( selectLocator, optionSpecifier )

        Verifies that the selected option of a drop-down satisfies the optionSpecifier.

        examples:

            verifySelected 	dropdown2 	John Smith
            verifySelected 	dropdown2 	value=js*123
            assertSelected 	document.forms[2].dropDown 	label=J* Smith
            assertSelected 	document.forms[2].dropDown 	index=0

    assertSelectOptions( selectLocator, optionLabelList )

        Verifies the labels of all options in a drop-down against a comma-separated list. Commas in an expected option can be escaped as ",".

        examples:

            verifySelectOptions 	dropdown2 	John Smith,Dave Bird
            assertSelectOptions 	document.forms[2].dropDown 	Smith\, J,Bird\, D

    assertText( elementLocator, textPattern )

        Verifies the text of an element. This works for any element that contains text. This command uses either the textContent (Mozilla-like browsers) or the innerText (IE-like browsers) of the element, which is the rendered text shown to the user.

        examples:

            verifyText 	statusMessage 	Successful
            assertText 	//div[@id='foo']//h1 	Successful

    assertAttribute( elementLocator@attributeName, valuePattern )

        Verifies the value of an element attribute.

        examples:

            verifyAttribute 	txt1@class 	bigAndBold
            assertAttribute 	document.images[0]@alt 	alt-text
            verifyAttribute 	//img[@id='foo']/@alt 	alt-text

    assertTextPresent( text )

        Verifies that the specified text appears somewhere on the rendered page shown to the user.

        examples:

            verifyTextPresent 	You are now logged in.
            assertTextPresent 	You are now logged in.

    assertTextNotPresent( text )

        Verifies that the specified text does NOT appear anywhere on the rendered page.

    assertElementPresent( elementLocator )

        Verifies that the specified element is somewhere on the page.

        examples:

            verifyElementPresent 	submitButton
            assertElementPresent 	//img[@alt='foo']

    assertElementNotPresent( elementLocator )

        Verifies that the specified element is NOT on the page.

        examples:

            verifyElementNotPresent 	cancelButton
            assertElementNotPresent 	cancelButton

    assertTable( cellAddress, valuePattern )

        Verifies the text in a cell of a table. The cellAddress syntax tableName.row.column, where row and column start at 0.

        examples:

            verifyTable 	myTable.1.6 	Submitted
            assertTable 	results.0.2 	13

    assertVisible( elementLocator )

        Verifies that the specified element is both present and visible. An element can be rendered invisible by setting the CSS "visibility" property to "hidden", or the "display" property to "none", either for the element itself or one if its ancestors.

        examples:

            verifyVisible 	postcode
            assertVisible 	postcode

    assertNotVisible( elementLocator )

        Verifies that the specified element is NOT visible. Elements that are simply not present are also considered invisible.

        examples:

            verifyNotVisible 	postcode
            assertNotVisible 	postcode

    verifyEditable / assertEditable( inputLocator )

        Verifies that the specified element is editable, ie. it's an input element, and hasn't been disabled.

        examples:

            verifyEditable 	shape
            assertEditable 	colour

    assertNotEditable( inputLocator )

        Verifies that the specified element is NOT editable, ie. it's NOT an input element, or has been disabled.

    assertAlert( messagePattern )

        Verifies that a javascript alert with the specified message was generated. Alerts must be verified in the same order that they were generated.

        Verifying an alert has the same effect as manually clicking OK. If an alert is generated but you do not verify it, the next Selenium action will fail.

        NOTE: under Selenium, javascript alerts will NOT pop up a visible alert dialog.

        NOTE: Selenium does NOT support javascript alerts that are generated in a page's onload() event handler. In this case a visible dialog WILL be generated and Selenium will hang until you manually click OK.

        examples:

            verifyAlert 	Invalid Phone Number
            assertAlert 	Invalid Phone Number

    assertConfirmation( messagePattern )

        Verifies that a javascript confirmation dialog with the specified message was generated. Like alerts, confirmations must be verified in the same order that they were generated.

        By default, the confirm function will return true, having the same effect as manually clicking OK. This can be changed by prior execution of the chooseCancelOnNextConfirmation command (see above). If an confirmation is generated but you do not verify it, the next Selenium action will fail.

        NOTE: under Selenium, javascript confirmations will NOT pop up a visible dialog.

        NOTE: Selenium does NOT support javascript confirmations that are generated in a page's onload() event handler. In this case a visible dialog WILL be generated and Selenium will hang until you manually click OK.

        examples:

            assertConfirmation 	Remove this user?
            verifyConfirmation 	Are you sure?

    assertPrompt( messagePattern )

        Verifies that a javascript prompt dialog with the specified message was generated. Like alerts, prompts must be verified in the same order that they were generated.

        Successful handling of the prompt requires prior execution of the answerOnNextPrompt command (see above). If a prompt is generated but you do not verify it, the next Selenium action will fail.

        examples:

            answerOnNextPrompt 	Joe
            click 	id=delegate
            verifyPrompt 	Delegate to who?

Parameter construction and Variables

    All Selenium command parameters can be constructed using both simple variable substitution as well as full javascript. Both of these mechanisms can access previously stored variables, but do so using different syntax.

    Stored Variables

    The commands store, storeValue and storeText can be used to store a variable value for later access. Internally, these variables are stored in a map called "storedVars", with values keyed by the variable name. These commands are documented in the command reference.

    Variable substitution

    Variable substitution provides a simple way to include a previously stored variable in a command parameter. This is a simple mechanism, by which the variable to substitute is indicated by ${variableName}. Multiple variables can be substituted, and intermixed with static text.

    Example:

        store 	Mr 	title
        storeValue 	nameField 	surname
        store 	${title} ${surname} 	fullname
        type 	textElement 	Full name is: ${fullname}

    Javascript evaluation

    Javascript evaluation provides the full power of javascript in constructing a command parameter. To use this mechanism, the entire parameter value must be prefixed by 'javascript{' with a trailing '}'. The text inside the braces is evaluated as a javascript expression, and can access previously stored variables using the storedVars map detailed above. Note that variable substitution cannot be combined with javascript evaluation.

    Example:

        store 	javascript{'merchant' + (new Date()).getTime()} 	merchantId
        type 	textElement 	javascript{storedVars['merchantId'].toUpperCase()}