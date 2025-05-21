# Upgrading Instructions for PRADO Framework v4.3.1

### !!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is,
if you want to upgrade from version A to version C and there is
version B between A and C, you need to following the instructions
for both A and B.

Upgrading from v4.2.2
---------------------
- Deprecated TTextBoxAutoCompleteType values have been removed since they are not supported anymore by no browser.
  The only permitted values are None, Enabled or Disabled.
- TDbLogRoute updates the table fields by adding the `prefix` field:  `ALTER TABLE pradolog ADD COLUMN prefix VARCHAR(128) AFTER category;`

Upgrading from v4.2.1
---------------------
- The Prado::getDefaultPermissions() function is now deprecated and has been replaced by Prado::getDefaultDirPermissions() and Prado::getDefaultFilePermissions(). If you have defined PRADO_CHMOD in your index.php file, please replace it with PRADO_DIR_CHMOD and PRADO_FILE_CHMOD.
- The way PRADO determines what service to instantiate when receiving a request has been changed. If you experience problems, the old behavior can be restored setting the "request" module to use the "ServiceOrder" ResolveMethod in application configuration.

Upgrading from v4.1.2
---------------------
- Application parameter "PluginContentId" is added for integrating PRADO Composer Extensions' pages into an application.  All PRADO Composer extensions use "PluginContentId" for their TContent ID to integrate into any particular application's layouts.
- Wsat has been moved into its own repo; if you use it, you may want to add to your composer.json: "pradosoft/prado-wsat": "*"
- the prado-cli command used to create a new project has been removed. Use "composer create-project pradosoft/prado-app <directory>" instead.
- TEACache has been removed. The eAccelerator project has been abandoned and doesn't work with PHP > 5.4.
- TXCache has been removed. The XCache project has been abandoned and doesn't work with PHP > 5.6.
- TFastSqlMapApplicationCache has been removed. It depended to an unavailable external DxUtil library.
- Previously the Prado class was an empty subclass of PradoBase, so that the Prado class could be overloaded.
  Since the introduction of namespaces this is not doable anymore, so the PradoBase class has been removed and all the methods have been moved to the Prado class.
- TUserManager::switchToGuest($user) has been removed. Instead, call $user->setIsGuest(true);
- TEnumerable::next() doesn't return an item anymore. You need to explicitly call TEnumerable::current() afterwards.
- TCache::offsetUnset() doesn't return a boolean result anymore.
- T(Active)HtmlArea4 has been replaced by T(Active)HtmlArea5, based on tinyMCE version 5. If you are using custom options/plugins, you may want to check if they need to be adapted for the new version.
- Prado doesn't bundle anymore a copy of the Bootstrap library; this forced every project to use the very same version of the library, but right now three incompatible version of the library exists (3, 4 and 5). If you were using Prado's provided version of the library, you may want to add to your project's composer.json a requirement for "bower-asset/bootstrap": "^3.3", and replace your <TStyleSheet PradoStyles="bootstrap"> tags with a direct inclusions of the library js and css files.

Upgrading from v4.1.1
---------------------
- The return value for getSelectedValue() method on list controls (eg. TDropDownList::getSelectedValue()) has changed. Previously, when no item was selected it would always return an empty string. Now it will return the PromptValue; note that PromptValue still defaults to an empty string.

Upgrading from v4.1.0
---------------------
- TMemCache has been updated to be compatible with memcached: https://www.php.net/manual/en/book.memcached.php:
  The per-server "Timeout" and "RetryInterval" properties have been removed, use the class property "Options" instead.
  The per-server "Persistent" property has been removed, use the class property "PersistentID" instead.
- The ImageAlign property of TImage and its subclasses is deprecated. Use the Style property to set the float and/or vertical-align CSS properties instead.
- THyperLink now has a new ImageStyle property that can be used to set the CSS style of the inner image (if any); the old ImageWidth, ImageHeight and ImageAlign properties are deprecated.

Upgrading from v4.0.2
---------------------
- Php 7.1 is now required.

Upgrading from v4.0.1
---------------------
- List controls (eg. TDropDownList) can now render empty an PromptValue. Previously, they would use PromptText in order to fill PromptValue when it was empty or not specified.
- A few internal classes from the Prado\I18N\core namespace have been removed: DateFormat, DateTimeFormatInfo, HTTPNegotiator, NumberFormat, NumberFormatInfo; The related components TDateFormat and TNumberFormat are still available, but they are based on php's intl extension.

Upgrading from v4.0.0
---------------------
- Prado 4.0.1 requires Php >= 5.4.0
- Removed deprecated TSqliteCache class; use TDbCache instead
- Removed deprecated iterator classes: TListIterator, TMapIterator, TStackIterator; use ArrayIterator instead
- Removed deprecated message source classes: MessageSource_MySQL and MessageSource_SQLite; use MessageSource_Database instead
- Removed deprecated STATE_OFF, STATE_DEBUG, STATE_NORMAL, STATE_PERFORMANCE constants from TApplication; use TApplicationMode constants instead
- Removed deprecated ST_AUTO, ST_START, ST_STEP, ST_FINISH and ST_COMPLETE constants from TWizard; use TWizardStepType constants instead
- Removed deprecated DataItem property from TDataListItem, TRepeaterItem and TDataGridItem classes; use Data instead
- Removed deprecated IT_HEADER, IT_FOOTER, IT_ITEM, IT_SEPARATOR, IT_ALTERNATINGITEM, IT_EDITITEM, IT_SELECTEDITEM, IT_PAGER constants from TRepeater and TDataGrid; use TListItemType constants instead.
- Removed deprecated CompressionOptions and EnableCompression properties from THtmlArea and THtmlArea4
- Removed deprecated quoteFunction() and isFunction() static methods from TJavaScript class; use quoteJsLiteral() and isJsLiteral() istead
- Removed deprecated HasPriority property from TCallbackClientSide; no replacement available, as it was effectless since 3.3.0
- Removed TDraggable, TDraggableConstraint, TDraggableGhostingOptions and TDraggableRevertOptions classes; use TJuiDraggable instead
- Removed TDropContainer and TDropContainerEventParameter classes; use TJuiDroppable instead
- Removed TAutoComplete, TAutoCompleteEventParameter and TAutoCompleteTemplate classes; use TJuiAutoComplete instead
- Removed autocomplete, dragdrop, dragdropextra, prototype and scriptaculous PradoScript (javascript) packages
- Removed deprecated TDateTimeStamp class; use php's getdate() or preferably the \DateTime class instead

Upgrading from v3.3.x
---------------------
- Removed RepackUTF7 property from TSafeHtml; it never became a standard.
- Removed LineNumberStyle property from TTextHighlighter; use the css class "hljs-line-numbers" instead.
- The THtmlArea's EnableCompression property is deprecated; enable gzip compression on the web server for better results.
- Removed mcrypt support in TSecurityManager; OpenSSL is now used instead. Since the old default encryption
  cipher rijndael-256 has no equivalent in OpenSSL, the new default cipher in OpenSSL is aes-256-cbc; be sure to
  migrate needed old encrypted data since it will become unencryptable afterwards.

Upgrading from v3.3.0
---------------------
- The long-time deprecated 'MySQL' translation source has been removed.
  The 'Database' source can be used instead, specifying a valid ConnectionID
  in the source parameter:
  <translation type="Database" source="db1" autosave="true" cache="false" />

Upgrading from v3.2.x
---------------------
- Since PRADO 3.3.0, jQuery is the javascript framework of choice. All the existing PRADO controls have
  already been ported, and prototype is still included to provide backward compatibility for custom controls.
  Anyway, updating custom controls is probably a good idea. Have a look at the "Upgrading from v3.2" page in
  the Quickstart tutorial for more informations.

Upgrading from v3.2.1
---------------------
- TEmailAddressValidator's CheckMXRecord property now defaults to false.

Upgrading from v3.2.0
---------------------
- The TSecurityManagerValidationMode class and TSecurityManager's Validation property have been deprecated.
  Instead, use the new TSecurityManager's HashAlgorithm property that permits the use of any hashing
  algorithm supported by the local php installation.
- TSecurityManager's Encryption property has been deprecated (it was unusable). Instead, use the new
  CryptAlgorithm property that permites the use of any algorithm supported by the local php installation.
- TDateTimeStamp has been deprecated in favour of php's native DateTime classes. TDateTimeStamp has been
  rewritten as a wrapper to DateTime, so porting and testing old code should be as easy as just looking at
  the new implementation.

Upgrading from v3.1.10
---------------------
- Prado 3.2 requires PHP >= 5.3.3
- Prado 3.2 doesn't use anymore a separate clientscripts.php script to publish minified javascript files.
  If you were relying (linking directly) to that script to get some js file, you'll need to re-adapt your
  scripts. Remember, linking directly a file in the assets/ directory is always a bad idea, let Prado do
  it for you using publishAssets().
- The removal of clientscripts.php lead to the removal of TClientScriptManager::registerJavascriptPackages()
  and the entire TClientScriptLoader class. These two were used to publish multiple javascript files at once
  and to compress/minify them. While the compression is something that is probably better done at the
  webserver/processor level, you can still get your scripts minified using TJavaScript::JSMin();
- Ticket #325 enabled progressive rendering inside Prado. Previously, all the output of Prado was
  buffered and sent to the client all at once. Now, you can decide to render smaller parts of the page
  and send them to client right after completed, one after the other (see TFlushOutput documentation).
  To ensure proper working of components requiring "head" resources (like external javascript files),
  all the resource publishing needs to be made before the actual rendering occurs. Tipically the best
  idea is to do it inside the onPreRender event. All the Prado components have been (obviously) updated
  to support this change, but any custom component made by yourself could need some update efforts.
  The easiest way to understand if a component is broken because of this change is to check if you get a
  'Operation invalid when page is already rendering' exception.
- A new "TReCaptcha" control has been added to overcome the limited security offered by TCaptcha. If you
  are currently using TCaptcha, an update to the new control is really adviced.
- Since php 5.2 the "sqlite" extension is not built by default. As a consequence, we deprecated (but kept
  to mantain backwards compatibility) everything based on that extension.
  TSqliteCache should be abandoned in favour of TDbCache.
  The "sqlite" backend for message translation has been deprecated, use "Database" instead.
- TPageService's default pages path has changed from "Application.pages" to "Application.Pages" (note the
  uppercase P). Using capital letters for the initial letter of the directories name is a long-time
  convention in prado, and this has been changed to reflect it. TPageService has been patched anyway to
  support even the old "Application.pages" to avoid breaking existing code.
- All the THttpRequest's methods used to gather server informations have been paired to return null if no
  information is available. Previously some of them returned an empty string (getQueryString and
  getHttpProtocolVersion), some other returned null, others caused a php NOTICE.
- Some TJavaScript methods have been modified to clear their use and provide better xss protection:
  1. the undocumented quoteUTF8() was removed, since it didn't provide any real protection;
  2. quoteString() now safely adds quotes around a string: previously it only added escape characters;
  3. the json* family of methods actually checks for errors and generate exceptions on fail;
  4. strings beginning with "javascript:", enclosed in {..} or [..] were previously meant to bypass any
  encoding in TJavascript::encode(): this could introduce xss vulnerabilities. Now everything always gets
  encoded, if you need a string to bypass encoding, prepare it with TJavaScript::quoteJsLiteral(). To
  achieve the same result on control properties defined in a template, prefix the property name with
  "js" and prado will figure it out automatically.
  to explicitly use TJavascript::quoteFunction() to ensure raw javascript will be published.
- The php JSON extension is required; it ships by default with php 5.3 and is a lot faster that the old
  TJSON-based implementation. TJSON has been removed, if you were calling it directly to encode/decode
  you can switch to TJavaScript::jsonEncode(), TJavaScript::jsonDecode().
- TActiveCustomValidator behaviour changed. Previously it was using a separate callback to perform
  validation on its own, while now it performs validation inside the main callback of the control that
  triggered the validation.

Upgrading from v3.1.9
---------------------

Upgrading from v3.1.8
---------------------
- An new "TranslateDefaultCulture" option has been added to TGlobalization that lets you choose if Prado
  have to translate the default culture (default up to 3.1.7) or not (changed in 3.1.8). This option is
  enabled by default, in fact restoring the pre-3.1.8 behaviour of translating also the default culture.
  You want this option to be enabled if:
  - you write pseudo translation tags in your code like <%[page_title_welcome]%> and need Prado to insert
    the proper translation for every language (i.e. the base text is not written in a real language);
  - your default culture is different from the culture used in your project (eg. your DefaultCulture is
    "fr", but text in your pages is written in english to ensure other team members will understand it);
  You want this option to be disabled if:
  - you write code in your DefaultCulture language like <%[Welcome to my website]%>. For users viewing
    your pages in that same Culture, Prado won't even try to translate these strings. Translation will
    occur normally for every other culture.

Upgrading from v3.1.7
---------------------
- behavior of THttpRequest::getBaseUrl() and THttpRequest::getAbsoluteApplicationUrl() changed:
	null - keep current schema
	true - force https
	false - force http
  relevance, only if invoking methods with explicit "false"


Upgrading from v3.1.6
---------------------
- The different SQLMap cache engines (TSQLMapFifoCache, TSQLMapLRUCache, TSQLMapApplicationCache) doesn't
take anymore the cache size in their constructor. Instead, they take the cachemodel object who instanciated them.
It shouldn't affect existing code, except if you instanciate one of this cache directly (i.e, without a <cachemodel>
directive in your SQLMap configuration)

Upgrading from v3.1.5
---------------------

Upgrading from v3.1.4
---------------------
- The structure of indices used by TDbCache has been changed by replacing
  PRIMARY KEY on 'itemkey' with non-unique index and adding an additional index on column 'expire'.
  Existing tables should be amended or deleted and recreated as follows:
  CREATE TABLE pradocache (itemkey CHAR(128), value BLOB, expire INT)
  CREATE INDEX IX_itemkey ON pradocache (itemkey)
  CREATE INDEX IX_expire ON pradocache (expire)

Upgrading from v3.1.3
---------------------
- The prado-cli and prado-cli.bat scripts have been moved into
  the framework folder of the distribution.


Upgrading from v3.1.2
---------------------
- The Translation configuration now also accepts type 'Database' to
  ease the setup of DB base translation. A valid ConnectionID has to
  be supplied in the source parameter:
  <translation type="Database" source="db1" autosave="true" cache="false" />
  Type 'MySQL' can still be used but is deprecated and might be removed
  in a later release.
- TinyMCE (used by THtmlArea component) has been upgraded to version 3.1.0.1.
  Since the 3.X branch of TinyMCE has a different API than 2.X, you should
  upgrade your Customs Plugins if you use any.
  See http://wiki.moxiecode.com/index.php/TinyMCE:Migration_guide for more information.
- If you use EnableStateEncryption, the PageState of your current user sessions
  will no longer be valid, since we optimized the encryption/compression logic.
- You can now use # and $ characters in your SQL statements with SQLMap by
  escaping them as ## and $$. That induces that you can't have consecutive
  parameters like #param1##param2# or $param1$$param2$ in your statements anymore.


Upgrading from v3.1.1
---------------------
- The RELATIONS type declaration in Active Record classes for Many-to-Many using
  an association table was change from "self::HAS_MANY" to "self::MANY_TO_MANY".
  E.g. change
     'albums' => array(self::HAS_MANY, 'Artist', 'album_artists')
  to
     'albums' => array(self::MANY_TO_MANY, 'Artist', 'album_artists')
- Active Record no longer automatically adds/removes/updates related objects.
- 'Raw' mode for TCheckboxList and TRadioButtonList (and their active counter parts) now render
  a surrounding <span> tag to allow client scripts to identify them with the ClientId. You may
  have to check your CSS.


Upgrading from v3.1.0
---------------------
- The RELATIONS declaration in Acive Record classes is changed from
  "protected static $RELATIONS" to "public static $RELATIONS".
- IFeedContentProvider adds a new method: getContentType(). This affects any
  class implementing this interface.
- TUrlMapping now only uses the PATH_INFO part of URL for matching, and the matching
  is for the whole PATH_INFO.
- IUserManager adds two new methods: getUserFromCookie() and saveUserToCookie().
  This affects classes that implements this interface and does not extend from
  TUserManager.
- The order of application lifecycles is changed. The loadState and loadStateComplete
  are moved to right after onBeginRequest.
- TDropDownList will be in an unselected state if no initial selection is specified.
  That is, its SelectedIndex will be -1. Previously, the first item will be considered as selected.

Upgrading from v3.1b
--------------------
- Comment tag <!-- ... ---> (introduced in v3.1a) is changed to <!--- ... --->
- When TDataList.RepeatLayout is Raw, the items will render <div> instead of <span>
- TActiveRecord finder methods will always return a new object instance (identity mapping was removed).
- TActiveRecord::findBySql() will return an object rather than an array
- TActiveRecord::findAllBySql() will return an array of objects.

Upgrading from v3.1a
---------------------
- The signature of TActiveRecord::finder() is changed. This affects
  all TActiveRecord-descendant classes that override this method.
  Please use the following code to override the method:
	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}

- The way to specify the table name for an active record class is changed.
  Previously, it used the static class member '_tablename'.
  Now it uses class constant as follows:
    class UserRecord extends TActiveRecord
    {
        const TABLE='users_table';
    }

- Changed TActiveRatingList's javascript control class
  name from "Prado.WebUI.TRatingList" to "Prado.WebUI.TActiveRatingList".

- PRADO's javascript library locations moved from Web/Javascripts/xxx to Web/Javascripts/source/xxx

- IPostBackDataHandler added a new method getDataChanged(). Any control
  implementing this interface will be required to implement this new method.

Upgrading from v3.0.x
---------------------
- Validators ClientSide.OnSuccess becomes ClientSide.OnValidationSuccess,
- Validators ClientSide.OnError becomes ClientSide.OnValidationError,
- Validator OnSuccess event becomes OnValidationSuccess.
- Validator OnError event becomes OnValidationError.
- Content enclosed in <!-- --> is now parsed as normal template content.
  Previously, it was not parsed and was rendered as is.

Upgrading from v3.0.7
---------------------

Upgrading from v3.0.6
---------------------

Upgrading from v3.0.5
---------------------
- TRepeater does not render <span> anymore for empty item template.
- constructUrl() now encodes ampersand by default. This should have minimal
  impact on any existing PRADO applications, though.
- TDataGrid does not generate default table styles. This may affect
  the appearance of existing PRADO applications that use TDataGrid.
- If TUrlMapping is used, you need to set the UrlManager property of
  THttpRequest to the module ID of TUrlMapping.
- TJavascriptLogger toggle key is changed from ALT-D to ALT-J.
   Use the ToggleKey property chanage to a different key.
- Javascript Library rico was REMOVED.

Upgrading from v3.0.4
---------------------
- TFileUpload::saveAs() will return false instead of raising an exception
  if it encounters any error.
- TDropDownListColumn.DataField is renamed to DataTextField and
  DataFormatString is renamed to DataTextFormatString.
  A new property named DataValueField is added.

Upgrading from v3.0.3
---------------------
- The 'Static' value is changed to 'Fixed' for the Display property of
  all validators as well as TValidationSummary, due to conflict with PHP keywords.
- The 'List' value is changed to 'SimpleList' for TValidationSummary.DisplayMode.
- The 'List' value is changed to 'DropDownList' for TPager.Mode
- This change affects existing client-side javascript handlers such as
  <com:TRequiredFieldValidator ClientSide.OnSuccess="xxx" />
  All ClientSide javascript event handlers (such as ClientSide.OnSuccess)
  are by default wrapped within the function block.
       function(sender, parameter){ // handler code }
  You may override this behaviour by providing your own javascript statement block
  as "javascript:MyHandlerFunction", e.g. ClientSide.OnSuccess="javascript:MyHandlerFunction"
  or ClientSide.OnSuccess="javascript:function(validator,sender){ ... }"


Upgrading from v3.0.2
---------------------
- The minimum PHP version required is raised to 5.1.0 and above.
  If your server is installed with a lower version of PHP, you will
  have to upgrade it in order to run PRADO applications.
- The signature of TControl::broadcastEvent() is changed from
  broadcastEvent($sender,TBroadCastEventParameter $param) to
  broadcastEvent($name,$sender,$param).
  This makes the call to broadcastEvent() to be consistent with raiseEvent().

Upgrading from v3.0.1
---------------------
- Postback enabled control will always disable default client-side browser action.
  This is due to google toolbar's interference of event stopping scheme.
  This modification should only affect user-derived postback javascripts.

Upgrading from v3.0.0
---------------------
- URL format is modified when THttpRequest.UrlFormat=='Path'.
  This modification affects both the URLs generated by calling constructUrl()
  and the URLs understood by PRADO. In particular, PRADO now understands
  the following URL format:
  /index.php/ServiceID,ServiceParam/Name1,Value1/Name2,Value2/...
  In v3.0.0, the above URL is written as:
  /index.php/ServiceID/ServiceParam/Name1/Value1/Name2/Value2/...
- TControl::onBubbleEvent() has been changed to TControl::bubbleEvent().
  This change only affects user controls that override this method.

Upgrading from v2.x and v1.x
----------------------------
PRADO v3.x is not backward compatible with v2.x and v1.x.
