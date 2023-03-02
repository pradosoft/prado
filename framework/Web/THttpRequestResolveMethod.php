<?php
/**
 * THttpRequestResolveMethod class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * THttpRequestResolveMethod class.
 * THttpRequestResolveMethod defines the method used to determine the
 * service that needs to be instanciated to handle the user request.
 *
 * The following enumerable values are defined:
 * - ServiceOrder: use the first service defined in the application
 *   configuration matching one of the request parameters
 * - ParameterOrder: use the first request parameter matching one of
 *   the services defined in application configuration.
 *
 * PRADO versions before 4.2.2 used the ServiceOrder method.
 * Since 4.2.2 the new default is ParameterOrder, that leads to a
 * better identification of the correct service when using the Path
 * and HiddenPath url formats.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.2.2
 */
class THttpRequestResolveMethod extends \Prado\TEnumerable
{
	public const ServiceOrder = 'ServiceOrder';
	public const ParameterOrder = 'ParameterOrder';
}
