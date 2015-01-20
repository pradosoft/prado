<?php
/**
 * TActiveFileUpload.php
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (issue 349 remote vulnerability fix)
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveFileUploadCallbackParams is an internal class used by {@link TActiveFileUpload}.
 *
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @package System.Web.UI.ActiveControls
 */
class TActiveFileUploadCallbackParams
{
	public $localName;
	public $fileName;
	public $fileSize;
	public $fileType;
	public $errorCode;
}