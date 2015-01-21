<?php
/**
 * TCaptcha class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

Prado::using('System.Web.UI.WebControls.TImage');

/**
 * TCaptcha class.
 *
 * Notice: while this class is easy to use and implement, it does not provide full security.
 * In fact, it's easy to bypass the checks reusing old, already-validated tokens (reply attack).
 * A better alternative is provided by {@link TReCaptcha}.
 *
 * TCaptcha displays a CAPTCHA (a token displayed as an image) that can be used
 * to determine if the input is entered by a real user instead of some program.
 *
 * Unlike other CAPTCHA scripts, TCaptcha does not need session or cookie.
 *
 * The token (a string consisting of alphanumeric characters) displayed is automatically
 * generated and can be configured in several ways. To specify the length of characters
 * in the token, set {@link setMinTokenLength MinTokenLength} and {@link setMaxTokenLength MaxTokenLength}.
 * To use case-insensitive comparison and generate upper-case-only token, set {@link setCaseSensitive CaseSensitive}
 * to false. Advanced users can try to set {@link setTokenAlphabet TokenAlphabet}, which
 * specifies what characters can appear in tokens.
 *
 * The validation of the token is related with two properties: {@link setTestLimit TestLimit}
 * and {@link setTokenExpiry TokenExpiry}. The former specifies how many times a token can
 * be tested with on the server side, and the latter says when a generated token will expire.
 *
 * To specify the appearance of the generated token image, set {@link setTokenImageTheme TokenImageTheme}
 * to be an integer between 0 and 63. And to adjust the generated image size, set {@link setTokenFontSize TokenFontSize}
 * (you may also set {@link TWebControl::setWidth Width}, but the scaled image may not look good.)
 * By setting {@link setChangingTokenBackground ChangingTokenBackground} to true, the image background
 * of the token will be variating even though the token is the same during postbacks.
 *
 * Upon postback, user input can be validated by calling {@link validate()}.
 * The {@link TCaptchaValidator} control can also be used to do validation, which provides
 * client-side validation besides the server-side validation.  By default, the token will
 * remain the same during multiple postbacks. A new one can be generated by calling
 * {@link regenerateToken()} manually.
 *
 * The following template shows a typical use of TCaptcha control:
 * <code>
 * <com:TCaptcha ID="Captcha" />
 * <com:TTextBox ID="Input" />
 * <com:TCaptchaValidator CaptchaControl="Captcha"
 *                        ControlToValidate="Input"
 *                        ErrorMessage="You are challenged!" />
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.1
 */
class TCaptcha extends TImage
{
	const MIN_TOKEN_LENGTH=2;
	const MAX_TOKEN_LENGTH=40;
	private $_privateKey;
	private $_validated=false;

	/**
	 * @return integer the theme of the token image. Defaults to 0.
	 */
	public function getTokenImageTheme()
	{
		return $this->getViewState('TokenImageTheme',0);
	}

	/**
	 * Sets the theme of the token image.
	 * You may test each theme to find out the one you like the most.
	 * Below is the explanation of the theme value:
	 * It is treated as a 5-bit integer. Each bit toggles a specific feature of the image.
	 * Bit 0 (the least significant): whether the image is opaque (1) or transparent (0).
	 * Bit 1: whether we should add white noise to the image (1) or not (0).
	 * Bit 2: whether we should add a grid to  the image (1) or not (0).
	 * Bit 3: whether we should add some scribbles to the image (1) or not (0).
	 * Bit 4: whether the image background should be morphed (1) or not (0).
	 * Bit 5: whether the token text should cast a shadow (1) or not (0).
	 * @param integer the theme of the token image. It must be an integer between 0 and 63.
	 */
	public function setTokenImageTheme($value)
	{
		$value=TPropertyValue::ensureInteger($value);
		if($value>=0 && $value<=63)
			$this->setViewState('TokenImageTheme',$value,0);
		else
			throw new TConfigurationException('captcha_tokenimagetheme_invalid',0,63);
	}

	/**
	 * @return integer the font size used for displaying the token in an image. Defaults to 30.
	 */
	public function getTokenFontSize()
	{
		return $this->getViewState('TokenFontSize',30);
	}

	/**
	 * Sets the font size used for displaying the token in an image.
	 * This property affects the generated token image size.
	 * The image width is proportional to this font size.
	 * @param integer the font size used for displaying the token in an image. It must be an integer between 20 and 100.
	 */
	public function setTokenFontSize($value)
	{
		$value=TPropertyValue::ensureInteger($value);
		if($value>=20 && $value<=100)
			$this->setViewState('TokenFontSize',$value,30);
		else
			throw new TConfigurationException('captcha_tokenfontsize_invalid',20,100);
	}

	/**
	 * @return integer the minimum length of the token. Defaults to 4.
	 */
	public function getMinTokenLength()
	{
		return $this->getViewState('MinTokenLength',4);
	}

	/**
	 * @param integer the minimum length of the token. It must be between 2 and 40.
	 */
	public function setMinTokenLength($value)
	{
		$length=TPropertyValue::ensureInteger($value);
		if($length>=self::MIN_TOKEN_LENGTH && $length<=self::MAX_TOKEN_LENGTH)
			$this->setViewState('MinTokenLength',$length,4);
		else
			throw new TConfigurationException('captcha_mintokenlength_invalid',self::MIN_TOKEN_LENGTH,self::MAX_TOKEN_LENGTH);
	}

	/**
	 * @return integer the maximum length of the token. Defaults to 6.
	 */
	public function getMaxTokenLength()
	{
		return $this->getViewState('MaxTokenLength',6);
	}

	/**
	 * @param integer the maximum length of the token. It must be between 2 and 40.
	 */
	public function setMaxTokenLength($value)
	{
		$length=TPropertyValue::ensureInteger($value);
		if($length>=self::MIN_TOKEN_LENGTH && $length<=self::MAX_TOKEN_LENGTH)
			$this->setViewState('MaxTokenLength',$length,6);
		else
			throw new TConfigurationException('captcha_maxtokenlength_invalid',self::MIN_TOKEN_LENGTH,self::MAX_TOKEN_LENGTH);
	}

	/**
	 * @return boolean whether the token should be treated as case-sensitive. Defaults to true.
	 */
	public function getCaseSensitive()
	{
		return $this->getViewState('CaseSensitive',true);
	}

	/**
	 * @param boolean whether the token should be treated as case-sensitive. If false, only upper-case letters will appear in the token.
	 */
	public function setCaseSensitive($value)
	{
		$this->setViewState('CaseSensitive',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the characters that may appear in the token. Defaults to '234578adefhijmnrtABDEFGHJLMNRT'.
	 */
	public function getTokenAlphabet()
	{
		return $this->getViewState('TokenAlphabet','234578adefhijmnrtABDEFGHJLMNRT');
	}

	/**
	 * @param string the characters that may appear in the token. At least 2 characters must be specified.
	 */
	public function setTokenAlphabet($value)
	{
		if(strlen($value)<2)
			throw new TConfigurationException('captcha_tokenalphabet_invalid');
		$this->setViewState('TokenAlphabet',$value,'234578adefhijmnrtABDEFGHJLMNRT');
	}

	/**
	 * @return integer the number of seconds that a generated token will remain valid. Defaults to 600 seconds (10 minutes).
	 */
	public function getTokenExpiry()
	{
		return $this->getViewState('TokenExpiry',600);
	}

	/**
	 * @param integer the number of seconds that a generated token will remain valid. A value smaller than 1 means the token will not expire.
	 */
	public function setTokenExpiry($value)
	{
		$this->setViewState('TokenExpiry',TPropertyValue::ensureInteger($value),600);
	}

	/**
	 * @return boolean whether the background of the token image should be variated during postbacks. Defaults to false.
	 */
	public function getChangingTokenBackground()
	{
		return $this->getViewState('ChangingTokenBackground',false);
	}

	/**
	 * @param boolean whether the background of the token image should be variated during postbacks.
	 */
	public function setChangingTokenBackground($value)
	{
		$this->setViewState('ChangingTokenBackground',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return integer how many times a generated token can be tested. Defaults to 5.
	 */
	public function getTestLimit()
	{
		return $this->getViewState('TestLimit',5);
	}

	/**
	 * @param integer how many times a generated token can be tested. For unlimited tests, set it to 0.
	 */
	public function setTestLimit($value)
	{
		$this->setViewState('TestLimit',TPropertyValue::ensureInteger($value),5);
	}

	/**
	 * @return boolean whether the currently generated token has expired.
	 */
	public function getIsTokenExpired()
	{
		if(($expiry=$this->getTokenExpiry())>0 && ($start=$this->getViewState('TokenGenerated',0))>0)
			return $expiry+$start<time();
		else
			return false;
	}

	/**
	 * @return string the public key used for generating the token. A random one will be generated and returned if this is not set.
	 */
	public function getPublicKey()
	{
		if(($publicKey=$this->getViewState('PublicKey',''))==='')
		{
			$publicKey=$this->generateRandomKey();
			$this->setPublicKey($publicKey);
		}
		return $publicKey;
	}

	/**
	 * @param string the public key used for generating the token. A random one will be generated if this is not set.
	 */
	public function setPublicKey($value)
	{
		$this->setViewState('PublicKey',$value,'');
	}

	/**
	 * @return string the token that will be displayed
	 */
	public function getToken()
	{
		return $this->generateToken($this->getPublicKey(),$this->getPrivateKey(),$this->getTokenAlphabet(),$this->getTokenLength(),$this->getCaseSensitive());
	}

	/**
	 * @return integer the length of the token to be generated.
	 */
	protected function getTokenLength()
	{
		if(($tokenLength=$this->getViewState('TokenLength'))===null)
		{
			$minLength=$this->getMinTokenLength();
			$maxLength=$this->getMaxTokenLength();
			if($minLength>$maxLength)
				$tokenLength=rand($maxLength,$minLength);
			else if($minLength<$maxLength)
				$tokenLength=rand($minLength,$maxLength);
			else
				$tokenLength=$minLength;
			$this->setViewState('TokenLength',$tokenLength);
		}
		return $tokenLength;
	}

	/**
	 * @return string the private key used for generating the token. This is randomly generated and kept in a file for persistency.
	 */
	public function getPrivateKey()
	{
		if($this->_privateKey===null)
		{
			$fileName=$this->generatePrivateKeyFile();
			$content=file_get_contents($fileName);
			$matches=array();
			if(preg_match("/privateKey='(.*?)'/ms",$content,$matches)>0)
				$this->_privateKey=$matches[1];
			else
				throw new TConfigurationException('captcha_privatekey_unknown');
		}
		return $this->_privateKey;
	}

	/**
	 * Validates a user input with the token.
	 * @param string user input
	 * @return boolean if the user input is not the same as the token.
	 */
	public function validate($input)
	{
		$number=$this->getViewState('TestNumber',0);
		if(!$this->_validated)
		{
			$this->setViewState('TestNumber',++$number);
			$this->_validated=true;
		}
		if($this->getIsTokenExpired() || (($limit=$this->getTestLimit())>0 && $number>$limit))
		{
			$this->regenerateToken();
			return false;
		}
		return ($this->getToken()===($this->getCaseSensitive()?$input:strtoupper($input)));
	}

	/**
	 * Regenerates the token to be displayed.
	 * By default, a token, once generated, will remain the same during the following page postbacks.
	 * Calling this method will generate a new token.
	 */
	public function regenerateToken()
	{
		$this->clearViewState('TokenLength');
		$this->setPublicKey('');
		$this->clearViewState('TokenGenerated');
		$this->clearViewState('RandomSeed');
		$this->clearViewState('TestNumber',0);
	}

	/**
	 * Configures the image URL that shows the token.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if(!self::checkRequirements())
			throw new TConfigurationException('captcha_imagettftext_required');
		if(!$this->getViewState('TokenGenerated',0))
		{
			$manager=$this->getApplication()->getAssetManager();
			$manager->publishFilePath($this->getFontFile());
			$url=$manager->publishFilePath($this->getCaptchaScriptFile());
			$url.='?options='.urlencode($this->getTokenImageOptions());
			$this->setImageUrl($url);

			$this->setViewState('TokenGenerated',time());
		}
	}

	/**
	 * @return string the options to be passed to the token image generator
	 */
	protected function getTokenImageOptions()
	{
		$privateKey=$this->getPrivateKey();  // call this method to ensure private key is generated
		$token=$this->getToken();
		$options=array();
		$options['publicKey']=$this->getPublicKey();
		$options['tokenLength']=strlen($token);
		$options['caseSensitive']=$this->getCaseSensitive();
		$options['alphabet']=$this->getTokenAlphabet();
		$options['fontSize']=$this->getTokenFontSize();
		$options['theme']=$this->getTokenImageTheme();
		if(($randomSeed=$this->getViewState('RandomSeed',0))===0)
		{
			$randomSeed=(int)(microtime()*1000000);
			$this->setViewState('RandomSeed',$randomSeed);
		}
		$options['randomSeed']=$this->getChangingTokenBackground()?0:$randomSeed;
		$str=serialize($options);
		return base64_encode(md5($privateKey.$str).$str);
	}

	/**
	 * @return string the file path of the PHP script generating the token image
	 */
	protected function getCaptchaScriptFile()
	{
		return dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'captcha.php';
	}

	protected function getFontFile()
	{
		return dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'verase.ttf';
	}

	/**
	 * Generates a file with a randomly generated private key.
	 * @return string the path of the file keeping the private key
	 */
	protected function generatePrivateKeyFile()
	{
		$captchaScript=$this->getCaptchaScriptFile();
		$path=dirname($this->getApplication()->getAssetManager()->getPublishedPath($captchaScript));
		$fileName=$path.DIRECTORY_SEPARATOR.'captcha_key.php';
		if(!is_file($fileName))
		{
			@mkdir($path);
			$key=$this->generateRandomKey();
			$content="<?php
\$privateKey='$key';
?>";
			file_put_contents($fileName,$content);
		}
		return $fileName;
	}

	/**
	 * @return string a randomly generated key
	 */
	protected function generateRandomKey()
	{
		return md5(rand().rand().rand().rand());
	}

	/**
	 * Generates the token.
	 * @param string public key
	 * @param string private key
	 * @param integer the length of the token
	 * @param boolean whether the token is case sensitive
	 * @return string the token generated.
	 */
	protected function generateToken($publicKey,$privateKey,$alphabet,$tokenLength,$caseSensitive)
	{
		$token=substr($this->hash2string(md5($publicKey.$privateKey),$alphabet).$this->hash2string(md5($privateKey.$publicKey),$alphabet),0,$tokenLength);
		return $caseSensitive?$token:strtoupper($token);
	}

	/**
	 * Converts a hash string into a string with characters consisting of alphanumeric characters.
	 * @param string the hexadecimal representation of the hash string
	 * @param string the alphabet used to represent the converted string. If empty, it means '234578adefhijmnrtwyABDEFGHIJLMNQRTWY', which excludes those confusing characters.
	 * @return string the converted string
	 */
	protected function hash2string($hex,$alphabet='')
	{
		if(strlen($alphabet)<2)
			$alphabet='234578adefhijmnrtABDEFGHJLMNQRT';
		$hexLength=strlen($hex);
		$base=strlen($alphabet);
		$result='';
		for($i=0;$i<$hexLength;$i+=6)
		{
			$number=hexdec(substr($hex,$i,6));
			while($number)
			{
				$result.=$alphabet[$number%$base];
				$number=floor($number/$base);
			}
		}
		return $result;
	}

	/**
	 * Checks the requirements needed for generating CAPTCHA images.
	 * TCaptach requires GD2 with TrueType font support and PNG image support.
	 * @return boolean whether the requirements are satisfied.
	 */
	public static function checkRequirements()
	{
		return extension_loaded('gd') && function_exists('imagettftext') && function_exists('imagepng');
	}
}

