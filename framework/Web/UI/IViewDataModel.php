<?php

/**
 * IViewDataModel interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * IViewDataModel interface
 *
 * A model that exchanges its data with the controls of a {@see TTemplateControl}.
 * {@see TTemplateControl::tryToUpdateView()} sets each value of {@see getViewData()}
 * on the control whose ID is the key. {@see TTemplateControl::tryToUpdateModel()}
 * reads the input controls and passes their values, keyed by control ID, to
 * {@see setViewData()}; the model takes the keys it owns and ignores the rest.
 *
 * The prado-activerecord `TActiveRecord` implements this contract: its view data
 * is the record's columns followed by its `$RELATIONS` entries, a `HAS_MANY`
 * relation as a list for a data-bound control, and `setViewData()` writes the
 * columns only.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IViewDataModel
{
	/**
	 * @return array<string, mixed> the model values keyed by field name; an iterable
	 *   value is a collection for a data-bound control
	 */
	public function getViewData(): array;

	/**
	 * @param array<string, mixed> $data values read from the view, keyed by control ID;
	 *   the model takes the keys it owns and ignores the rest
	 */
	public function setViewData(array $data): void;
}
