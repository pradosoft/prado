/**
 * Adapter: controls
 *
 * Exposes the public API of controls.js (and its dependency prado.js) as
 * named exports so that test files can import specific symbols by name.
 *
 * ESM migration path — replace the loadScript calls with:
 *
 *   export { WebUI } from
 *     '../../../framework/Web/Javascripts/source/prado/controls/controls.js';
 *
 * Test files importing from this adapter require no changes.
 */

import { loadScript } from '../helpers/loadScript.js';

// Load in dependency order.
loadScript('framework/Web/Javascripts/source/prado/prado.js');
loadScript('framework/Web/Javascripts/source/prado/controls/controls.js');

// Top-level namespace
export const WebUI = global.Prado.WebUI;

// Individual control classes
export const Control          = global.Prado.WebUI.Control;
export const PostBackControl  = global.Prado.WebUI.PostBackControl;
export const TButton          = global.Prado.WebUI.TButton;
export const TLinkButton      = global.Prado.WebUI.TLinkButton;
export const TCheckBox        = global.Prado.WebUI.TCheckBox;
export const TBulletedList    = global.Prado.WebUI.TBulletedList;
export const TImageMap        = global.Prado.WebUI.TImageMap;
export const TImageButton     = global.Prado.WebUI.TImageButton;
export const TRadioButton     = global.Prado.WebUI.TRadioButton;
export const TTextBox         = global.Prado.WebUI.TTextBox;
export const TListControl     = global.Prado.WebUI.TListControl;
export const TListBox         = global.Prado.WebUI.TListBox;
export const TDropDownList    = global.Prado.WebUI.TDropDownList;
export const DefaultButton    = global.Prado.WebUI.DefaultButton;
export const TTextHighlighter = global.Prado.WebUI.TTextHighlighter;
export const TCheckBoxList    = global.Prado.WebUI.TCheckBoxList;
export const TRadioButtonList = global.Prado.WebUI.TRadioButtonList;

// Expose core Prado globals needed by some tests
export const PostBack         = global.Prado.PostBack;
export const Registry         = global.Prado.Registry;
