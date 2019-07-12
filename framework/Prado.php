<?php
/**
 * Prado class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

/**
 * Prado class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
class Prado extends PradoBase
{
}

/**
 * Initialize Prado autoloader and error handler
 */
Prado::init();

/**
 * Defines Prado in global namespace
 */
class_alias('\Prado\Prado', 'Prado');
