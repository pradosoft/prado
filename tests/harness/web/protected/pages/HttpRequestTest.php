<?php

class HttpRequestTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
	}

	public function getInfoJson(): string
	{
		$req = $this->Request;
		return htmlspecialchars(json_encode([
			'method'          => $req->getRequestType(),
			'queryString'     => $req->getQueryString(),
			'requestUri'      => $req->getRequestUri(),
			'serverName'      => $req->getServerName(),
			'serverPort'      => (int) $req->getServerPort(),
			'userAgent'       => $req->getUserAgent(),
			'userHostAddress' => $req->getUserHostAddress(),
			'isSecure'        => $req->getIsSecureConnection(),
			'protocol'        => $req->getHttpProtocolVersion(),
			'baseUrl'         => $req->getBaseUrl(),
			'appUrl'          => $req->getApplicationUrl(),
			'languages'       => $req->getUserLanguages(),
			'headers'         => $req->getHeaders(CASE_LOWER),
			'foo'             => $req->itemAt('foo'),
			'postkey'         => $req->itemAt('postkey'),
		]), ENT_QUOTES);
	}
}
