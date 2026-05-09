import { TInPlaceTextBox } from './tests/js/adapters/inlineeditor.js';

describe('debug', () => {
  it('element check', () => {
    const container = document.createElement('div');
    const label = document.createElement('span');
    label.id = 'lbl_debug';
    label.innerHTML = 'Before';
    container.appendChild(label);
    document.body.appendChild(container);

    for (const k of Object.keys(global.Prado.Registry)) delete global.Prado.Registry[k];
    for (const k of Object.keys(TInPlaceTextBox.textboxes)) delete TInPlaceTextBox.textboxes[k];

    const ctrl = new TInPlaceTextBox({
      ID: 'lbl_debug',
      TextBoxID: 'tb_lbl_debug',
      EventTarget: 'lbl_debug',
      TextMode: 'SingleLine',
      ReadOnly: false,
      AutoPostBack: false,
      AutoHide: false,
      LoadTextOnEdit: false,
    });

    expect(ctrl.element).toBe(label);
    ctrl.onTextChangedSuccess({}, 'New label text');
    expect(label.innerHTML).toBe('New label text');
    container.remove();
  });
});
