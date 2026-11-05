<?php

/**
 * TProofOfWorkStartMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TProofOfWorkStartMode class.
 *
 * TProofOfWorkStartMode enumerates when a {@see TProofOfWork} starts solving its challenge.
 * - Focus: when focus first enters the form.
 * - Load: when the page loads.
 * - Submit: when the form is submitted; the submission waits for the solution.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProofOfWorkStartMode extends \Prado\TEnumerable
{
	public const Focus = 'Focus';
	public const Load = 'Load';
	public const Submit = 'Submit';
}
