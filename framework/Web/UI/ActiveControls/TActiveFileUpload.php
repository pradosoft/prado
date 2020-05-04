<?php
/**
 * TActiveFileUpload.php
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (issue 349 remote vulnerability fix)
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load TActiveControlAdapter and TFileUpload.
 */
use Exception;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\WebControls\TFileUpload;
use Prado\Web\UI\WebControls\THiddenField;
use Prado\Web\UI\WebControls\TImage;
use Prado\Web\UI\WebControls\TInlineFrame;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TActiveFileUpload
 *
 * TActiveFileUpload displays a file upload field on a page. Upon postback,
 * the text entered into the field will be treated as the name of the file
 * that will be uploaded to the server. The property {@link getHasFile HasFile}
 * indicates whether the file upload is successful. If successful, the file
 * may be obtained by calling {@link saveAs} to save it at a specified place.
 * You can use {@link getFileName FileName}, {@link getFileType FileType},
 * {@link getFileSize FileSize} to get the original client-side file name,
 * the file mime type, and the file size information. If the upload is not
 * successful, {@link getErrorCode ErrorCode} contains the error code
 * describing the cause of failure.
 *
 * TActiveFileUpload raises {@link onFileUpload OnFileUpload} event if a file is uploaded
 * (whether it succeeds or not).
 *
 * TActiveFileUpload actually does a postback in a hidden IFrame, and then does a callback.
 * This callback then raises the {@link onFileUpload OnFileUpload} event. After the postback
 * a status icon is displayed; either a green checkmark if the upload is successful,
 * or a red x if there was an error.
 *
 * TActiveFileUpload needs either an application level cache or a security manager to work securely.
 *
 * Since Prado 4.0 the TActiveFileUpload supports HTML5 multiple file uploads by setting the
 * {@link setMultiple Multiple} attribute to true. See the description of the parent class
 * {@see TFileUpload} for further details.
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 */
class TActiveFileUpload extends TFileUpload implements IActiveControl, ICallbackEventHandler, INamingContainer
{
	const SCRIPT_PATH = 'activefileupload';

	/**
	 * @var THiddenField a flag to tell which component is doing the callback.
	 */
	private $_flag;
	/**
	 * @var TImage that spins to show that the file is being uploaded.
	 */
	private $_busy;
	/**
	 * @var TImage that shows a green check mark for completed upload.
	 */
	private $_success;
	/**
	 * @var TImage that shows a red X for incomplete upload.
	 */
	private $_error;
	/**
	 * @var TInlineFrame used to submit the data in an "asynchronous" fashion.
	 */
	private $_target;
	/**
	 * @var class name used to instantiate items for uploaded files: {@link TFileUploadItem}
	 */
	protected static $fileUploadItemClass = '\Prado\Web\UI\ActiveControls\TActiveFileUploadItem';


	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}


	/**
	 * @param string $file asset file in the self::SCRIPT_PATH directory.
	 * @return string asset file url.
	 */
	protected function getAssetUrl($file = '')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl();
		return $base . '/' . self::SCRIPT_PATH . '/' . $file;
	}


	/**
	 * This method is invoked when a file is uploaded.
	 * If you override this method, be sure to call the parent implementation to ensure
	 * the invocation of the attached event handlers.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onFileUpload($param)
	{
		if ($this->_flag->getValue() && $this->getPage()->getIsPostBack() && $param == $this->_target->getUniqueID()) {
			$params = new TActiveFileUploadCallbackParams;
			// save the files so that they will persist past the end of this return.
			foreach ($this->getFiles() as $file) {
				$localName = str_replace('\\', '/', tempnam(Prado::getPathOfNamespace($this->getTempPath()), ''));
				$file->saveAs($localName);
				$file->setLocalName($localName);
				$params->files[] = $file->toArray();
			}

			// return some javascript to display a completion status.
			echo "<script>
          	 Options = new Object();
          	 Options.clientID = '{$this->getClientID()}';
          	 Options.targetID = '{$this->_target->getUniqueID()}';
          	 Options.errorCode = '" . (int) !$this->getHasAllFiles() . "';
          	 Options.callbackToken = '{$this->pushParamsAndGetToken($params)}';
          	 Options.fileName = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileName') : $this->getFileName(), JSON_HEX_APOS) . "';
             Options.fileSize = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileSize') : $this->getFileSize(), JSON_HEX_APOS) . "';
             Options.fileType = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileType') : $this->getFileType(), JSON_HEX_APOS) . "';
             Options.errorCode = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'errorCode') : $this->getErrorCode(), JSON_HEX_APOS) . "';
          	 parent.Prado.WebUI.TActiveFileUpload.onFileUpload(Options);
           </script>";

			exit();
		}
	}

	/**
	 * @return string the path where the uploaded file will be stored temporarily, in namespace format
	 * default "Application.runtime.*"
	 */
	public function getTempPath()
	{
		return $this->getViewState('TempPath', 'Application.runtime.*');
	}

	/**
	 * @param string $value the path where the uploaded file will be stored temporarily in namespace format
	 * default "Application.runtime.*"
	 */
	public function setTempPath($value)
	{
		$this->setViewState('TempPath', $value, 'Application.runtime.*');
	}

	/**
	 * @return bool a value indicating whether an automatic callback to the server will occur whenever the user modifies the text in the TTextBox control and then tabs out of the component. Defaults to true.
	 * Note: When set to false, you will need to trigger the callback yourself.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack', true);
	}

	/**
	 * @param bool $value a value indicating whether an automatic callback to the server will occur whenever the user modifies the text in the TTextBox control and then tabs out of the component. Defaults to true.
	 * Note: When set to false, you will need to trigger the callback yourself.
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string A chuck of javascript that will need to be called if {{@link getAutoPostBack AutoPostBack} is set to false}
	 */
	public function getCallbackJavascript()
	{
		return "Prado.WebUI.TActiveFileUpload.fileChanged(\"{$this->getClientID()}\")";
	}

	/**
	 * @param mixed $sender
	 * @throws TInvalidDataValueException if the {@link getTempPath TempPath} is not writable.
	 */
	public function onInit($sender)
	{
		parent::onInit($sender);

		if (!Prado::getApplication()->getCache()) {
			if (!Prado::getApplication()->getSecurityManager()) {
				throw new Exception('TActiveFileUpload needs either an application level cache or a security manager to work securely');
			}
		}

		if (!is_writable(Prado::getPathOfNamespace($this->getTempPath()))) {
			throw new TInvalidDataValueException("activefileupload_temppath_invalid", $this->getTempPath());
		}
	}

	/**
	 * Raises <b>OnFileUpload</b> event.
	 *
	 * This method is required by {@link ICallbackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$cp = $param->getCallbackParameter();
		if ($key = $cp->targetID == $this->_target->getUniqueID()) {
			$params = $this->popParamsByToken($cp->callbackToken);
			foreach ($params->files as $index => $file) {
				$_FILES[$key]['name'][$index] = stripslashes($file['fileName']);
				$_FILES[$key]['size'][$index] = (int) ($file['fileSize']);
				$_FILES[$key]['type'][$index] = $file['fileType'];
				$_FILES[$key]['error'][$index] = (int) ($file['errorCode']);
				$_FILES[$key]['tmp_name'][$index] = $file['localName'];
			}
			$this->loadPostData($key, null);

			$this->raiseEvent('OnFileUpload', $this, $param);
		}
	}

	/**
	 * Raises postdata changed event.
	 * This method calls {@link onFileUpload} method
	 * This method is primarily used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		$this->onFileUpload($this->getPage()->getRequest()->itemAt('TActiveFileUpload_TargetId'));
	}

	protected function pushParamsAndGetToken(TActiveFileUploadCallbackParams $params)
	{
		if ($cache = Prado::getApplication()->getCache()) {
			// this is the most secure method, file info can't be forged from client side, no matter what
			$token = md5('TActiveFileUpload::Params::' . $this->ClientID . '::' . rand(1000 * 1000, 9999 * 1000));
			$cache->set($token, serialize($params), 5 * 60); // expire in 5 minutes - the callback should arrive back in seconds, actually
		} elseif ($mgr = Prado::getApplication()->getSecurityManager()) {
			// this is a less secure method, file info can be still forged from client side, but only if attacker knows the secret application key
			$token = urlencode(base64_encode($mgr->encrypt(serialize($params))));
		} else {
			throw new Exception('TActiveFileUpload needs either an application level cache or a security manager to work securely');
		}

		return $token;
	}

	protected function popParamsByToken($token)
	{
		if ($cache = Prado::getApplication()->getCache()) {
			$v = $cache->get($token);
			assert($v != '');
			$cache->delete($token); // remove it from cache so it can't be used again and won't take up space either
			$params = unserialize($v);
		} elseif ($mgr = Prado::getApplication()->getSecurityManager()) {
			$v = $mgr->decrypt(base64_decode(urldecode($token)));
			$params = unserialize($v);
		} else {
			throw new Exception('TActiveFileUpload needs either an application level cache or a security manager to work securely');
		}

		assert($params instanceof TActiveFileUploadCallbackParams);

		return $params;
	}

	/**
	 * Publish the javascript
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		if (!$this->getPage()->getIsPostBack() && isset($_GET['TActiveFileUpload_InputId']) && isset($_GET['TActiveFileUpload_TargetId']) && $_GET['TActiveFileUpload_InputId'] == $this->getClientID()) {
			$params = new TActiveFileUploadCallbackParams;
			foreach ($this->getFiles() as $file) {
				$localName = str_replace('\\', '/', tempnam(Prado::getPathOfNamespace($this->getTempPath()), ''));
				$file->setLocalName($localName);
				// tricky workaround to intercept "uploaded file too big" error: real uploads happens in onFileUpload instead
				$file->setErrorCode(UPLOAD_ERR_FORM_SIZE);
				$params->files[] = $file->toArray();
			}

			echo "<script>
          	 Options = new Object();
          	 Options.clientID = '{$_GET['TActiveFileUpload_InputId']}';
          	 Options.targetID = '{$_GET['TActiveFileUpload_TargetId']}';
			       Options.errorCode = '" . (int) !$this->getHasAllFiles() . "';
          	 Options.callbackToken = '{$this->pushParamsAndGetToken($params)}';
          	 Options.fileName = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileName') : $this->getFileName(), JSON_HEX_APOS) . "';
             Options.fileSize = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileSize') : $this->getFileSize(), JSON_HEX_APOS) . "';
             Options.fileType = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'fileType') : $this->getFileType(), JSON_HEX_APOS) . "';
             Options.errorCode = '" . TJavaScript::jsonEncode($this->getMultiple() ? array_column($params->files, 'errorCode') : $this->getErrorCode(), JSON_HEX_APOS) . "';
           	 parent.Prado.WebUI.TActiveFileUpload.onFileUpload(Options);
           </script>";
		}
	}

	public function createChildControls()
	{
		$this->getPage()->getClientScript()->registerPradoScript('activefileupload');

		$this->_flag = new THiddenField;
		$this->_flag->setID('Flag');
		$this->getControls()->add($this->_flag);

		$this->_busy = new TImage;
		$this->_busy->setID('Busy');
		$this->_busy->setImageUrl($this->getAssetUrl('ActiveFileUploadIndicator.gif'));
		$this->_busy->setStyle("display:none");
		$this->getControls()->add($this->_busy);

		$this->_success = new TImage;
		$this->_success->setID('Success');
		$this->_success->setImageUrl($this->getAssetUrl('ActiveFileUploadComplete.png'));
		$this->_success->setStyle("display:none");
		$this->getControls()->add($this->_success);

		$this->_error = new TImage;
		$this->_error->setID('Error');
		$this->_error->setImageUrl($this->getAssetUrl('ActiveFileUploadError.png'));
		$this->_error->setStyle("display:none");
		$this->getControls()->add($this->_error);

		$this->_target = new TInlineFrame;
		$this->_target->setID('Target');
		$this->_target->setFrameUrl('about:blank');
		$this->_target->setStyle("width:0px; height:0px; border:none");
		$this->getControls()->add($this->_target);
	}

	/**
	 * Removes localfile on ending of the callback.
	 * @param mixed $param
	 */
	public function onUnload($param)
	{
		if ($this->getPage()->getIsCallback()) {
			foreach ($this->getFiles() as $file) {
				if ($file->getHasFile() && file_exists($file->getLocalName())) {
					unlink($file->getLocalName());
				}
			}
		}
		parent::onUnload($param);
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getAdapter()->getBaseActiveControl()->getClientSide();
	}

	/**
	 * Adds ID attribute, and renders the javascript for active component.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());

		$this->getActiveControl()->registerCallbackClientScript($this->getClientClassName(), $this->getClientOptions());
	}

	/**
	 * @return string corresponding javascript class name for this control.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveFileUpload';
	}

	/**
	 * Gets the client side options for this control.
	 * @return array (	inputID => input client ID,
	 * 					flagID => flag client ID,
	 * 					targetName => target unique ID,
	 * 					formID => form client ID,
	 * 					indicatorID => upload indicator client ID,
	 * 					completeID => complete client ID,
	 * 					errorID => error client ID)
	 */
	protected function getClientOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();

		$options['inputID'] = $this->getClientID();
		$options['flagID'] = $this->_flag->getClientID();
		$options['targetID'] = $this->_target->getUniqueID();
		$options['formID'] = $this->getPage()->getForm()->getClientID();
		$options['indicatorID'] = $this->_busy->getClientID();
		$options['completeID'] = $this->_success->getClientID();
		$options['errorID'] = $this->_error->getClientID();
		$options['autoPostBack'] = $this->getAutoPostBack();
		return $options;
	}

	/**
	 * @return TImage the image displayed when an upload
	 * 		completes successfully.
	 */
	public function getSuccessImage()
	{
		$this->ensureChildControls();
		return $this->_success;
	}

	/**
	 * @return TImage the image displayed when an upload
	 * 		does not complete successfully.
	 */
	public function getErrorImage()
	{
		$this->ensureChildControls();
		return $this->_error;
	}

	/**
	 * @return TImage the image displayed when an upload
	 * 		is in progress.
	 */
	public function getBusyImage()
	{
		$this->ensureChildControls();
		return $this->_busy;
	}
}
