/**
 * Adapter: activecontrols
 *
 * Exposes the public API of activecontrols3.js (and its dependency chain)
 * as named exports so that test files can import specific symbols by name.
 *
 * ESM migration path — replace the loadScript calls with direct imports
 * from the source files. Test files importing from this adapter require no
 * changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/ajax3.js');
loadScript('framework/Web/Javascripts/source/prado/activecontrols/activecontrols3.js');

// Top-level namespace
export const WebUI = global.Prado.WebUI;

// Shared callback infrastructure (from ajax3.js, needed for mocking)
export const CallbackRequestManager = global.Prado.CallbackRequestManager;
export const CallbackRequest        = global.Prado.CallbackRequest;
export const Registry               = global.Prado.Registry;

// Callback base class
export const CallbackControl          = global.Prado.WebUI.CallbackControl;

// Button-family controls
export const TActiveButton            = global.Prado.WebUI.TActiveButton;
export const TActiveLinkButton        = global.Prado.WebUI.TActiveLinkButton;
export const TActiveImageButton       = global.Prado.WebUI.TActiveImageButton;

// Checkbox / radio
export const TActiveCheckBox          = global.Prado.WebUI.TActiveCheckBox;
export const TActiveRadioButton       = global.Prado.WebUI.TActiveRadioButton;
export const TActiveCheckBoxList      = global.Prado.WebUI.TActiveCheckBoxList;
export const TActiveRadioButtonList   = global.Prado.WebUI.TActiveRadioButtonList;

// List controls
export const ActiveListControl        = global.Prado.WebUI.ActiveListControl;
export const TActiveDropDownList      = global.Prado.WebUI.TActiveDropDownList;
export const TActiveListBox           = global.Prado.WebUI.TActiveListBox;

// Text box
export const TActiveTextBox           = global.Prado.WebUI.TActiveTextBox;

// Trigger controls
export const TTimeTriggeredCallback   = global.Prado.WebUI.TTimeTriggeredCallback;
export const TEventTriggeredCallback  = global.Prado.WebUI.TEventTriggeredCallback;
export const TValueTriggeredCallback  = global.Prado.WebUI.TValueTriggeredCallback;

// Table controls
export const TActiveTableCell         = global.Prado.WebUI.TActiveTableCell;
export const TActiveTableRow          = global.Prado.WebUI.TActiveTableRow;
