<?php
/**
 * CAPTCHA generator script.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: $
 * @package System.Web.UI.WebControls.assets
 */

require_once(dirname(__FILE__).'/captcha_key.php');

$token='error';
if(isset($_GET['options']))
{
	$str=base64_decode($_GET['options']);
	if(strlen($str)>32)
	{
		$hash=substr($str,0,32);
		$str=substr($str,32);
		if(md5($privateKey.$str)===$hash)
		{
			$options=unserialize($str);
			$publicKey=$options['publicKey'];
			$tokenLength=$options['tokenLength'];
			$caseSensitive=$options['caseSensitive'];
			$alphabet=$options['alphabet'];
			$token=generateToken($publicKey,$privateKey,$alphabet,$tokenLength,$caseSensitive);
		}
	}
}

displayToken($token);

function generateToken($publicKey,$privateKey,$alphabet,$tokenLength,$caseSensitive)
{
	$token=substr(hash2string(md5($publicKey.$privateKey),$alphabet).hash2string(md5($privateKey.$publicKey),$alphabet),0,$tokenLength);
	return $caseSensitive?$token:strtoupper($token);
}

function hash2string($hex,$alphabet='')
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

function displayToken($token)
{
	$length=strlen($token);
	$width=45*$length;
	$height=70;
	$image=imagecreatetruecolor($width,$height);
	$font=dirname(__FILE__).DIRECTORY_SEPARATOR.'verase.ttf';
	$vred=rand(0,100);
	$vgreen=rand(0,100);
	$vblue=rand(0,100);
	for($x=0;$x<$width;++$x)
	{
		for($y=0;$y<$height;++$y)
		{
			$vred+=rand(-2,2);
			$vgreen+=rand(-2,2);
			$vblue+=rand(-2,2);
			if($vred<0) $vred=0; if($vred>150) $vred=75;
			if($vgreen<0) $vgreen=0; if($vgreen>150) $vgreen=75;
			if($vblue<0) $vblue=0; if($vblue>150) $vblue=75;
			$col = imagecolorallocate($image, $vred, $vgreen, $vblue);
			imagesetpixel($image, $x, $y, $col);
            imagecolordeallocate($image, $col);
		}
	}

    imagefilter($image,IMG_FILTER_GAUSSIAN_BLUR);
    for($i=0;$i<$length;$i++)
	{
        $vred = rand(150, 240);
		$vgreen = rand(150, 240);
		$vblue = rand(150, 240);
        $col = imagecolorallocate($image, $vred, $vgreen, $vblue);
        $char = $token[$i];
        imagettftext($image, rand(40, 50), rand(-10, 20), 13 + (40 * $i), rand(50, imagesy($image) - 10), $col, $font, $char);
        imagecolordeallocate($image, $col);
    }
    imagefilter($image,IMG_FILTER_GAUSSIAN_BLUR);

	imagepng($image);
	imagedestroy($image);
}

?>