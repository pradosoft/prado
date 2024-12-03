<?php

/**
 * TSkinTemplate class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * TSkinTemplate implements TTemplate but without class and attribute validation.
 * This class is the implementation of skin files in themes. Skin errors are thrown
 * on setting object skin properties rather than on parsing the skin.  Skins can
 * have class objects that are not implemented to be more portable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TSkinTemplate extends TTemplate
{
	/**
	 * Constructor.
	 * turns off attribute validation
	 * @param string $template the template string
	 * @param string $contextPath the template context directory
	 * @param null|string $tplFile the template file, null if no file
	 * @param int $startingLine the line number that parsing starts from (internal use)
	 * @param bool $sourceTemplate whether this template is a source template, i.e., this template is loaded from
	 * some external storage rather than from within another template.
	 */
	public function __construct($template, $contextPath, $tplFile = null, $startingLine = 0, $sourceTemplate = true)
	{
		$this->setAttributeValidation(false);
		parent::__construct($template, $contextPath, $tplFile, $startingLine, $sourceTemplate);
	}
}
