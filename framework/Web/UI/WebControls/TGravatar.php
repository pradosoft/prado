<?php
/**
 * TGravatar class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TGravatar class.
 *
 * TGravatar extends TImage and outputs a gravatar ImageUrl based
 * upon an email address, size, rating, and default style of the gravatar.
 *
 * {@see setDefaultImageStyle} allows for various styles of gravatar:
 *		• mp - Mystery Person
 *		• identicon - identicon style
 *		• monsterid - monster style
 *		• wavatar - wavatar style
 *		• retro - Retro style
 *		• robohash - Robohash style
 *		• blank - a blank space
 *		• 404 - not found page error
 *		• _url - provide your own default URL (to be url encoded for you)
 *
 * A rating of the gravatar can be provided as g, pg, r, and x.
 *
 * The size must be between 1 and 512, inclusive.
 *
 * {@see \Prado\Util\Behaviors\TParameterizeBehavior} can be attached to TGravatar to give
 * default values for various properties like DefaultImageStyle and Rating.
 * PRADO Skins can also be used to provide default values.
 *
 * See {@see https://gravatar.com} for more information.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TGravatar extends TImage
{
	public const HTTP_URL = 'http://www.gravatar.com/avatar/';
	public const HTTPS_URL = 'https://secure.gravatar.com/avatar/';

	/**
	 * @return string the URL to the gravatar
	 */
	public function getImageUrl()
	{
		$params = [];
		$params['s'] = $this->getSize();
		$params['r'] = $this->getRating();
		$params['d'] = $this->getDefaultImageStyle();

		return ($this->getUseSecureUrl() ? self::HTTPS_URL : self::HTTP_URL) . md5(strtolower(trim($this->getEmail()))) . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
	}

	/**
	 * @return null|string one of: mp, identicon, monsterid, wavatar, retro, robohash, blank, 404, _url_;
	 * this defaults to null
	 */
	public function getDefaultImageStyle()
	{
		return $this->getViewState('default');
	}

	/**
	 * @param null|string $default one of: mp, identicon, monsterid, wavatar, retro, robohash, blank, 404, _url_
	 */
	public function setDefaultImageStyle($default)
	{
		$default = TPropertyValue::ensureString($default);
		$lowerDefault = strtolower($default);
		if ($valid = in_array($lowerDefault, ['mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank', '404', ''])) {
			$default = $lowerDefault;
		}
		if (!$valid && !preg_match('/^https?:\/\//i', $default)) {
			throw new TInvalidDataValueException('gravatar_bad_default', $default);
		}
		if (!$default) {
			$default = null;
		}
		$this->setViewState('default', $default);
	}

	/**
	 * When the Size is not set or is null, the default size of a gravatar
	 * from the gravatar website is 80.
	 * @return null|int the pixel size of the gravatar, [1..512], default null (results in 80)
	 */
	public function getSize()
	{
		return $this->getViewState('size');
	}

	/**
	 * @param null|int $size the pixel size of the gravatar, [1..512]
	 */
	public function setSize($size)
	{
		$_size = TPropertyValue::ensureInteger($size);
		if (($_size > 512 || $_size < 1) && $size !== null && $size !== '') {
			throw new TInvalidDataValueException('gravatar_bad_size', $size);
		}
		if (!$size) {
			$_size = null;
		}
		$this->setViewState('size', $_size);
	}

	/**
	 * @return null|string the rating of the icon ['g', 'pg', 'r', 'x', ''], default null
	 */
	public function getRating()
	{
		return $this->getViewState('rating');
	}

	/**
	 * @param null|string $rating the rating of the icon ['g', 'pg', 'r', 'x', '']
	 */
	public function setRating($rating)
	{
		$rating = strtolower(TPropertyValue::ensureString($rating));
		$rating = TPropertyValue::ensureEnum($rating, ['g', 'pg', 'r', 'x', '']);
		if (!$rating) {
			$rating = null;
		}
		$this->setViewState('rating', $rating);
	}

	/**
	 * @return string the email address associated with the gravatar icon
	 */
	public function getEmail()
	{
		return $this->getViewState('email', '');
	}

	/**
	 * @param string $email the email address associated with the gravatar icon
	 */
	public function setEmail($email)
	{
		$this->setViewState('email', TPropertyValue::ensureString($email), '');
	}

	/**
	 * @return bool whether or not to use the secure HTTPS url, defaults to the connection being used
	 */
	public function getUseSecureUrl()
	{
		return $this->getViewState('use_secure_url', $this->getRequest()->getIsSecureConnection());
	}

	/**
	 * @param bool $useSecureUrl whether or not to use the secure HTTPS url
	 */
	public function setUseSecureUrl($useSecureUrl)
	{
		$this->setViewState('use_secure_url', TPropertyValue::ensureBoolean($useSecureUrl));
	}
}
