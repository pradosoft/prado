import { test, expect } from '@playwright/test';
import { demosHelper } from '../../helpers.js';

test('QuickstartTextBoxTestCase', async ({ page }) => {
  const h = demosHelper(page);

  await h.url('quickstart/index.php?page=Controls.Samples.TTextBox.Home&notheme=true&lang=en');

  await h.assertTitle('PRADO QuickStart Sample');

  // a normal textbox
  await h.type('ctl0$body$ctl0', 'test');

  // textbox with maxlength set
  await h.assertAttribute('ctl0$body$ctl1@size', '4');
  await h.assertAttribute('ctl0$body$ctl1@maxlength', '6');
  await h.type('ctl0$body$ctl1', 'textte');

  // vCard textboxes
  await h.assertAttribute('ctl0$body$ctl2@autocomplete', 'off');
  await h.type('ctl0$body$ctl2', 'first');
  await h.assertAttribute('ctl0$body$ctl3@autocomplete', 'on');
  await h.type('ctl0$body$ctl3', 'last');

  // a disabled textbox
  await h.assertAttribute('ctl0$body$ctl4@disabled', 'regexp:true|disabled');

  // a read-only textbox
  await h.assertAttribute('ctl0$body$ctl5@readonly', 'regexp:true|readonly');

  // auto postback textbox, CausesValidation=false
  await h.assertValue('ctl0$body$ctl6', 'change me');
  await h.typeSpecial('ctl0$body$ctl6', 'change mes');
  await h.assertValue('ctl0$body$ctl6', 'text changed');

  // auto postback textbox, CausesValidation=true
  await h.assertNotVisible('ctl0_body_ctl7');
  await h.typeSpecial('ctl0$body$TextBox3', 'test');
  await h.assertVisible('ctl0_body_ctl7');
  await h.typeSpecial('ctl0$body$TextBox3', 'non test');
  await h.assertNotVisible('ctl0_body_ctl7');

  // submitting textbox with a button
  await h.type('ctl0$body$TextBox1', 'texttext');
  await h.byXPath("//input[@type='submit' and @value='Submit']").click();
  await h.assertValue('ctl0$body$TextBox1', "You just entered 'texttext'.");

  // SafeText
  await h.assertText('ctl0_body_Output', 'test');
  await h.typeSpecial('ctl0$body$TextBox2', '<a href=javascript:xxx>malicious code</a>');
  await h.pause(50);
  await h.assertText('ctl0_body_Output', 'malicious code');

  // password
  await h.assertAttribute('ctl0$body$ctl9@type', 'password');

  // ------------------multiline textbox----------------------

  // regular textbox
  await h.type('ctl0$body$ctl10', 'This is a\nmultiline\ntextbox.');
  await h.type('ctl0$body$ctl11', 'This is a multiline text box.\nIn HTML, it is displayed as a textarea.\nEnd of message\n');

  // a disabled multiline textbox
  await h.assertAttribute('ctl0$body$ctl12@disabled', 'regexp:true|disabled');

  // a read-only multiline textbox
  await h.assertAttribute('ctl0$body$ctl13@readonly', 'regexp:true|readonly');
  await h.assertAttribute('ctl0$body$ctl13@wrap', 'off');

  // auto postback textbox
  await h.assertValue('ctl0$body$ctl14', 'change me');
  await h.typeSpecial('ctl0$body$ctl14', 'change mes');
  await h.assertValue('ctl0$body$ctl14', 'text changed');
  await h.assertValue('ctl0$body$ctl10', 'This is a\nmultiline\ntextbox.');
  await h.assertValue('ctl0$body$ctl11', 'This is a multiline text box.\nIn HTML, it is displayed as a textarea.\nEnd of message\n');

  // textbox associated with a validator
  await h.assertNotVisible('ctl0_body_ctl15');
  await h.typeSpecial('ctl0$body$MultiTextBox3', 'demo');
  await h.assertVisible('ctl0_body_ctl15');
  await h.typeSpecial('ctl0$body$MultiTextBox3', 'non demo');
  await h.assertNotVisible('ctl0_body_ctl15');
});
