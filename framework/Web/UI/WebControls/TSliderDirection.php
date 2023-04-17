<?php
/**
 * TSlider class file.
 *
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.1.1
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSliderDirection enum.
 *
 * TSliderDirection defines the enumerable type for the possible direction that can be used in a {@link TSlider}
 *
 * The following enumerable values are defined :
 * - Horizontal : Horizontal slider
 * - Vertical : Vertical slider
 *
 * @author Christophe Boulain <Christophe.Boulain@gmail.com>
 * @since 3.1.1
 */
enum TSliderDirection: string
{
	case Horizontal = 'Horizontal';
	case Vertical = 'Vertical';
}
