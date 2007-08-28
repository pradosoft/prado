<?php

require_once(dirname(__FILE__).'/captcha_key.php');

if(isset($_GET['pk']))
	echo $_GET['pk'].$privateKey;
else
	echo $privateKey;

function generateToken($publicKey,$privateKey,$tokenLength,$caseSensitive)
{
	$token=substr(hash2string(md5($publicKey.$privateKey)).hash2string(md5($privateKey.$publicKey)),0,$tokenLength);
	return $caseSensitive?$token:strtoupper($token);
}

function hash2string($hex,$alphabet='')
{
	if(strlen($alphabet)<2)
		$alphabet='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
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

?>