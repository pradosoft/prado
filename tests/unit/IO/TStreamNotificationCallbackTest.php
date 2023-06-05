<?php

use Prado\IO\TStreamNotificationCallback;
use Prado\IO\TStreamNotificationParameter;
use Prado\Prado;

class TTestStreamNotificationCallback extends TStreamNotificationCallback
{
	public mixed $pubProperty = null;
	public mixed $_wrapProperty = null;
	public mixed $pubPropertyOuter = null;
	public mixed $_wrapPropertyOuter = null;
	
	public function getWrapProperty(): mixed
	{
		return $this->_wrapProperty;
	}
	
	public function setWrapProperty(mixed $value)
	{
		$this->_wrapProperty = $value;
	}
	
	public function getWrapPropertyOuter(): mixed
	{
		return $this->_wrapPropertyOuter;
	}
	
	public function setWrapPropertyOuter(mixed $value)
	{
		$this->_wrapPropertyOuter = $value;
	}
}

class TStreamNotificationCallbackTest extends PHPUnit\Framework\TestCase
{
	public bool $testNetwork = false;
	
	public function testEvents()
	{
		$direct = false;
		$notification = new TStreamNotificationCallback($closure = function($nc, $s, $m, $mc, $t, $mb) use (&$direct) {$direct = 1;});
		self::assertTrue($notification->getCallbacks()->contains($closure));
		
		$resolve = $authRequired = $authResult = $redirect = $connected = $fileSize = $mimeType = $progress = $completed = $failure = false;
		$resolveP = $authRequiredP = $authResultP = $redirectP = $connectedP = $fileSizeP = $mimeTypeP = $progressP = $completedP = $failureP = false;
		
		$notification->onResolve[] = function ($sender, $param) use (&$resolve, &$resolveP) {
			$resolve = true;
			$resolveP = $param;
		};
		$notification->onConnected[] = function ($sender, $param) use (&$connected, &$connectedP) {
			$connected = true;
			$connectedP = $param;
		};
		$notification->onAuthRequired[] = function ($sender, $param) use (&$authRequired, &$authRequiredP) {
			$authRequired = true;
			$authRequiredP = $param;
		};
		$notification->onAuthResult[] = function ($sender, $param) use (&$authResult, &$authResultP) {
			$authResult = true;
			$authResultP = $param;
		};
		$notification->onRedirected[] = function ($sender, $param) use (&$redirect, &$redirectP) {
			$redirect = true;
			$redirectP = $param;
		};
		$notification->onMimeType[] = function ($sender, $param) use (&$mimeType, &$mimeTypeP) {
			$mimeType = true;
			$mimeTypeP = $param;
		};
		$notification->onFileSize[] = function ($sender, $param) use (&$fileSize, &$fileSizeP) {
			$fileSize = true;
			$fileSizeP = $param;
		};
		$notification->onProgress[] = function ($sender, $param) use (&$progress, &$progressP) {
			$progress = true;
			$progressP = $param;
		};
		$notification->onCompleted[] = function ($sender, $param) use (&$completed, &$completedP) {
			$completed = true;
			$completedP = $param;
		};
		$notification->onFailure[] = function ($sender, $param) use (&$failure, &$failureP) {
			$failure = true;
			$failureP = $param;
		};
		$param = new TStreamNotificationParameter();
		
		$notification(STREAM_NOTIFY_RESOLVE, 2, 'message', 404, 50, 100);
		self::assertEquals(1, $direct);
		self::assertTrue($resolve);
		$param = $resolveP;
		self::assertEquals($param, $notification->getParameter());
		self::assertEquals(STREAM_NOTIFY_RESOLVE, $param->getNotificationCode());
		self::assertEquals(2, $param->getSeverity());
		self::assertEquals('message', $param->getMessage());
		self::assertEquals(404, $param->getMessageCode());
		self::assertEquals(50, $param->getBytesTransferred());
		self::assertEquals(100, $param->getBytesMax());
		$resolve = $resolveP = false;
		
		$notification(STREAM_NOTIFY_CONNECT, 1, 'msg', 200, 25, 30);
		self::assertTrue($connected);
		self::assertEquals($param, $connectedP);
		self::assertEquals(STREAM_NOTIFY_CONNECT, $param->getNotificationCode());
		self::assertEquals(1, $param->getSeverity());
		self::assertEquals('msg', $param->getMessage());
		self::assertEquals(200, $param->getMessageCode());
		self::assertEquals(25, $param->getBytesTransferred());
		self::assertEquals(30, $param->getBytesMax());
		$connected = $connectedP = false;
		
		$notification(STREAM_NOTIFY_AUTH_REQUIRED, 0, null, 0, 0, 0);
		self::assertTrue($authRequired);
		self::assertEquals($param, $authRequiredP);
		$authRequired = $authRequiredP = false;
		
		$notification(STREAM_NOTIFY_AUTH_RESULT, 0, null, 0, 0, 0);
		self::assertTrue($authResult);
		self::assertEquals($param, $authResultP);
		$authResult = $authResultP = false;
		
		$notification(STREAM_NOTIFY_REDIRECTED, 0, null, 0, 0, 0);
		self::assertTrue($redirect);
		self::assertEquals($param, $redirectP);
		$redirect = $redirectP = false;
		
		$notification(STREAM_NOTIFY_MIME_TYPE_IS, 0, 'text/plain; charset=utf-8', 0, 0, 0);
		self::assertTrue($mimeType);
		self::assertEquals($param, $mimeTypeP);
		self::assertEquals('text/plain', $notification->getMimeType());
		self::assertEquals('utf-8', $notification->getCharSet());
		$mimeType = $mimeTypeP = false;
		
		$notification(STREAM_NOTIFY_FILE_SIZE_IS, 0, null, 0, 1, 15);
		self::assertTrue($fileSize);
		self::assertEquals($param, $fileSizeP);
		self::assertEquals(15, $notification->getFileSize());
		self::assertEquals(1, $notification->getBytesTransferred());
		$fileSize = $fileSizeP = false;
		
		$notification(STREAM_NOTIFY_PROGRESS, 0, null, 0, 2, 15);
		self::assertTrue($progress);
		self::assertEquals($param, $progressP);
		self::assertEquals(2, $notification->getBytesTransferred());
		$progress = $progressP = false;
		
		self::assertFalse($notification->getIsCompleted());
		$notification(STREAM_NOTIFY_COMPLETED, 0, null, 0, 0, 0);
		self::assertTrue($completed);
		self::assertEquals($param, $completedP);
		self::assertTrue($notification->getIsCompleted());
		$completed = $completedP = false;
		
		self::assertFalse($notification->getIsFailure());
		$notification(STREAM_NOTIFY_FAILURE, 2, null, 403, 0, 0);
		self::assertTrue($failure);
		self::assertEquals($param, $failureP);
		self::assertEquals(2, $notification->getSeverity());
		self::assertTrue($notification->getIsFailure());
		self::assertEquals(403, $notification->getMessageCode());
		$failure = $failureP = false;
		
	}
	
	public function testFilterStreamContext()
	{
		$connected1 = $connected2 = false;
		$fileSize1 = $fileSize2 = false;
		$mimeType1 = $mimeType2 = false;
		$progress1 = $progress2 = false;
		
		$options = ['notification' => [
				'class' => TTestStreamNotificationCallback::class,
				'pubProperty' => $v1 = 'pubValue',
				'wrapProperty' => $v2 = 'wrapValue',
				'onconnected' => $f1 = function($s, $p) use (&$connected1) {$connected1 = true;}, 
				'onfilesize' => [$f3 = function($s, $p) use (&$fileSize1) {$fileSize1 = true;}], 
				'onmimetype' => $f5 = function($s, $p) use (&$mimeType1) {$mimeType1 = true;}, 
				'onprogress' => $f7 = function($s, $p) use (&$progress1) {$progress1 = true;}], 
			'onconnected' => $f2 = function($s, $p) use (&$connected2) {$connected2 = true;}, 
			'onfilesize' => [$f4 = function($s, $p) use (&$fileSize2) {$fileSize2 = true;}], 
			'onmimetype' => $f6 = function($s, $p) use (&$mimeType2) {$mimeType2 = true;}, 
			'onprogress' => $f8 = function($s, $p) use (&$progress2) {$progress2 = true;},
			'pubPropertyOuter' => $v3 = 'pubValueOuter',
			'wrapPropertyOuter' => $v4 = 'wrapValueOuter',
			'http' => ['user_agent' => $userAgent = 'Prado/' . Prado::getVersion()]];
		$context = TStreamNotificationCallback::filterStreamContext($options);
		$callback = TStreamNotificationCallback::getContextNotificationCallback($context);
		$params = stream_context_get_params($context);
		self::assertInstanceOf(TStreamNotificationCallback::class, $callback);
		self::assertEquals($v1, $callback->pubProperty);
		self::assertEquals($v2, $callback->wrapProperty);
		self::assertEquals($v3, $callback->pubPropertyOuter);
		self::assertEquals($v4, $callback->wrapPropertyOuter);
		self::assertEquals($f1, $callback->onConnected[0]);
		self::assertEquals($f2, $callback->onConnected[1]);
		self::assertEquals($f3, $callback->onFileSize[0]);
		self::assertEquals($f4, $callback->onFileSize[1]);
		self::assertEquals($f5, $callback->onMimeType[0]);
		self::assertEquals($f6, $callback->onMimeType[1]);
		self::assertEquals($f7, $callback->onProgress[0]);
		self::assertEquals($f8, $callback->onProgress[1]);
		self::assertEquals($userAgent, $params['options']['http']['user_agent']);
		
		$options = ['notification' => [
				'onconnected' => $f1 = function($s, $p) use (&$connected1) {$connected1 = true;}, 
				'onfilesize' => [$f3 = function($s, $p) use (&$fileSize1) {$fileSize1 = true;}], 
				'onmimetype' => $f5 = function($s, $p) use (&$mimeType1) {$mimeType1 = true;}, 
				'onprogress' => $f7 = function($s, $p) use (&$progress1) {$progress1 = true;}], 
			'onconnected' => $f2 = function($s, $p) use (&$connected2) {$connected2 = true;}, 
			'onfilesize' => [$f4 = function($s, $p) use (&$fileSize2) {$fileSize2 = true;}], 
			'onmimetype' => $f6 = function($s, $p) use (&$mimeType2) {$mimeType2 = true;}, 
			'onprogress' => $f8 = function($s, $p) use (&$progress2) {$progress2 = true;},
			'http' => ['user_agent' => $userAgent = 'Prado/' . Prado::getVersion()]];
		$context = TStreamNotificationCallback::filterStreamContext($options);
		$callback = TStreamNotificationCallback::getContextNotificationCallback($context);
		$params = stream_context_get_params($context);
		self::assertInstanceOf(TStreamNotificationCallback::class, $callback);
		self::assertEquals($f1, $callback->onConnected[0]);
		self::assertEquals($f2, $callback->onConnected[1]);
		self::assertEquals($f3, $callback->onFileSize[0]);
		self::assertEquals($f4, $callback->onFileSize[1]);
		self::assertEquals($f5, $callback->onMimeType[0]);
		self::assertEquals($f6, $callback->onMimeType[1]);
		self::assertEquals($f7, $callback->onProgress[0]);
		self::assertEquals($f8, $callback->onProgress[1]);
		self::assertEquals($userAgent, $params['options']['http']['user_agent']);
		
		$closure = function($nc, $s, $m, $mc, $t, $mb) use (&$direct) {$direct = 1;};
		
		$options = ['notification' => $closure, 
			'onconnected' => $f1 = function($s, $p) use (&$connected2) {$connected2 = true;}, 
			'onfilesize' => $f2 = function($s, $p) use (&$fileSize2) {$fileSize2 = true;}, 
			'onmimetype' => [$f3 = function($s, $p) use (&$mimeType2) {$mimeType2 = true;}], 
			'onprogress' => [$f4 = function($s, $p) use (&$progress2) {$progress2 = true;}],
			'http' => ['user_agent' => $userAgent = 'PRADO/' . Prado::getVersion()]];
		$context = TStreamNotificationCallback::filterStreamContext($options);
		$callback = TStreamNotificationCallback::getContextNotificationCallback($context);
		$params = stream_context_get_params($context);
		self::assertEquals($closure, $callback->getCallbacks()[0]);
		self::assertEquals($f1, $callback->onConnected[0]);
		self::assertEquals($f2, $callback->onFileSize[0]);
		self::assertEquals($f3, $callback->onMimeType[0]);
		self::assertEquals($f4, $callback->onProgress[0]);
		self::assertEquals($userAgent, $params['options']['http']['user_agent']);
		
		$options = ['http' => ['user_agent' => $userAgent = 'prado/' . Prado::getVersion()]];
		$context = TStreamNotificationCallback::filterStreamContext($options);
		$params = stream_context_get_params($context);
		
		self::assertFalse(array_key_exists('notification', $params));
		self::assertEquals($userAgent, $params['options']['http']['user_agent']);
	}
	
	public function testInvoke()
	{
		if (!$this->testNetwork) {
			throw new PHPUnit\Framework\IncompleteTestError();
		}
		
		$direct = $resolve = $connected = $fileSize = $mimeType = $progress = false;
		
		$notification = new TStreamNotificationCallback(function($nc, $s, $m, $mc, $t, $mb) use (&$direct) {$direct = 1;});
		
		$url = 'https://raw.githubusercontent.com/pradosoft/prado/master/README.md';
		$context = stream_context_create([], ['notification' => $notification]);
		$notification->onConnected[] = function ($sender, $param) use (&$connected) {
			$connected = 2;
		};
		$notification->onFileSize[] = function ($sender, $param) use (&$fileSize) {
			$fileSize = 3;
		};
		$notification->onMimetype[] = function ($sender, $param) use (&$mimeType) {
			$mimeType = 4;
		};
		$notification->onProgress[] = function ($sender, $param) use (&$progress) {
			$progress = 5;
		};
		$stream = fopen($url, 'rb', false, $context);
		
		self::assertEquals('text/plain', $notification->getMimeType());
		self::assertEquals('utf-8', $notification->getCharset());
		self::assertNull($notification->getMessageCode());
		$data = stream_get_contents($stream);
		self::assertFalse($notification->getIsCompleted()); // not this, but for sockets completed-closed.
		self::assertFalse($notification->getIsFailure());
		fclose($stream);
		
		self::assertEquals(1, $direct);
		self::assertEquals(2, $connected);
		self::assertEquals(3, $fileSize);
		self::assertEquals(4, $mimeType);
		self::assertEquals(5, $progress);
		
	}

	public function testInvoke_Failure()
	{
		if (!$this->testNetwork) {
			throw new PHPUnit\Framework\IncompleteTestError();
		}
		$notification = new TStreamNotificationCallback();
		
		$url = 'https://raw.githubusercontent.com/pradosoft/prado/master/NO_FILE_FOUND.txt';
		$context = stream_context_create([], ['notification' => $notification]);
		$stream = @fopen($url, 'rb', false, $context);
		self::assertEquals(false, $stream);
		self::assertTrue($notification->getIsFailure());
		self::assertEquals(404, $notification->getMessageCode());
	}
}
