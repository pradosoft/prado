# Web/UI/WebControls/TFileValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFileValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFileValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0 (issue #636)

## Overview
TFileValidator validates the files selected in a [TFileUpload](./TFileUpload.md) (or `TActiveFileUpload`) control. Each file is checked against size, extension, and MIME type restrictions; the file count is checked against min/max limits. With `EnableClientScript` (default true) the same checks run in the browser through the HTML5 File API before the files upload, avoiding the transfer of files the server would reject.

Validation succeeds when no file is selected — pair with `TRequiredFieldValidator` to require a selection. A file with a PHP upload error code (`UPLOAD_ERR_FORM_SIZE`, `UPLOAD_ERR_PARTIAL`, …) fails validation.

## Inheritance

`TFileValidator` → `TBaseValidator` → `TLabel` → `TWebControl` → `TControl` → `TComponent`

Client-side class: `Prado.WebUI.TFileValidator` (`framework/Web/Javascripts/source/prado/validator/validation3.js`).

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `MaxFileSize` | int | `0` | Maximum bytes per file. `0` → falls back to the target's `TFileUpload::MaxFileSize` (1 MB default). |
| `MinFileSize` | int | `0` | Minimum bytes per file. `0` disables the check. |
| `TotalMaxFileSize` | int | `0` | Maximum combined bytes of all selected files. `0` disables the check. Helps a `Multiple` selection stay under `post_max_size`. (@since 4.4.0) |
| `MaxFileCount` | int | `0` | Maximum number of files. `0` disables the check. |
| `MinFileCount` | int | `0` | Minimum number of files when at least one is selected. `0` disables the check. |
| `AllowedFileExtensions` | string | `''` | Comma/space separated extensions (`"jpg, png"` or `".jpg"`), case-insensitive. |
| `AllowedFileTypes` | string | `''` | Comma/space separated MIME types; `image/*` matches every subtype. |
| `CheckExtensionMimeType` | bool | `false` | Server-only. The `fileinfo`-sniffed content type must correspond to the file name extension (map in `static::$extensionMimeTypes`, extendable by subclasses). Detects renamed files. Unknown extensions, extensionless names, and unavailable sniffing pass. |
| `InvalidFileNames` | string[] | `[]` | Read-only. Names of the files that failed the last validation. |

## Matching Semantics

| Configuration | Rule |
|---|---|
| `AllowedFileExtensions` and/or `AllowedFileTypes` set | The file must match every non-empty list (AND). |
| Both empty, target has `Accept` property or `accept` attribute | Restrictions derive from the Accept tokens; the file must match any token (OR), mirroring the HTML file picker. `.jpg` tokens match the extension, MIME tokens match the type. |
| Both empty, no Accept | Only size, count, and error-code checks apply. |

## Server-side MIME Detection

`getFileMimeType()` sniffs the uploaded file content with the `fileinfo` extension when available (`finfo_file` on `LocalName`), falling back to the untrusted browser-supplied `FileType`. The client side can only check the browser-reported `file.type`.

## `{files}` ErrorMessage Token

`ErrorMessage="Wrong type: {files}"` — both sides replace `{files}` with the comma-separated invalid file names (HTML-encoded server-side; `textContent` client-side). The client options keep the raw token (`getClientScriptOptions()` resets `ErrorMessage` to the unsubstituted value).

## Patterns & Gotchas

- **Target must be a TFileUpload** — `evaluateIsValid()` throws `TConfigurationException` (`filevalidator_fileupload_required`) otherwise.
- **The client JS does not use `getValidationValue()`** — it reads `this.control.files` directly, so `TBaseValidator::$_clientClass` and the `getRawValidationValue()` switches stay untouched and `TRequiredFieldValidator` on file inputs keeps its `control.value` fakepath behavior.
- **`MaxFileSize=0` still enforces a limit** — the target's `MaxFileSize` (default 1 MB) is the fallback; PHP enforces the same value through the `MAX_FILE_SIZE` hidden field anyway.
- **Client `file.type` can be empty** for unknown types — a type-restricted validator then rejects the file client-side; the server re-checks with `fileinfo`.

## Subclasses

[TImageValidator](./TImageValidator.md) adds pixel dimension restrictions and a readable-image check.

## TActiveFileUpload Integration (@since 4.4.0)

`TActiveFileUpload` with `CausesValidation` (default true) runs this control's validators client-side before its auto-upload starts and validates the page server-side during the upload callback — see [TActiveFileUpload](../ActiveControls/TActiveFileUpload.md) for the timing trap (validate during the callback, not a later postback).

## Tests

- Unit: `tests/unit/Web/UI/WebControls/TFileValidatorTest.php`, `TFileUploadTest.php`
- JS (vitest): `tests/js/validator/filevalidator.test.js`
- Functional (Playwright): `tests/playwright/validators/FileValidatorTestCase.spec.js` + `tests/harness/validators/protected/pages/FileValidator.page`
