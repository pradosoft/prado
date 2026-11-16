# Util/Email/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Email`**

## Purpose

Email sub-system (issue #760, since 4.4.0): emails rendered from `.email` templates like pages, sent through pluggable mailer behaviors, and, with the database module, logged, queued, tracked, unsubscribed, and matched to bounces.

## Classes

- **`TEmailModule`** — Creates emails from `Emails/<Path>.email` templates (`createEmail()`), sends them (`sendEmail()`, `sendMail()`), and raises `onSendEmail`. Attaches `TAppEmailerBehavior` to the application and `TNativeMailBehavior` as the default mailer. `processBounceMessage()` parses a bounce and raises `onBounce` per failed recipient.
- **`TDbEmailModule`** — Extends `TEmailModule`; uses `TDbPropertiesTrait` (ConnectionID, or SQLite `emails.db` in the runtime path). Logs every email (`Logging`), queues with `OfflineEmail` (`sendPendingEmails()`), records opens, clicks, unsubscribes (`recordOpen/Click/Unsubscribe()`, events `onOpenEmail`, `onClickEmail`, `onUnsubscribe`), and records bounces by Message-ID (`onBounce`). A nested `<service>` registers `TDbEmailService`; `ServiceUrl` is required when rendering without a web request. Supplies the `emailsender` and `emailcleaner` cron tasks.
- **`TDbEmailService`** — Serves an email by tracking ID: `open` (1x1 GIF), `click=N` (redirect to registered link N only), `unsubscribe` (GET confirms, POST/RFC 8058 one-click unsubscribes), `file`, or the email in the browser. A `Secure` email is shown only to its owner.
- **`TEmail`** — The message: addresses, headers, bodies with `{{KEY}}` custom data, attachments (`addInlineAttachment()` for `cid:`), tracking ID (random 128 bits), tracked links, `Secure`, `setListUnsubscribe()`.
- **`TEmailAddress`**, **`TEmailConfiguration`**, **`TEmailEventParameter`** — Address value, email defaults configuration, `onSendEmail` parameter.
- **`TEmailBounce`**, **`TEmailBounceParser`** — One failed or delayed recipient; parser for RFC 3464 DSNs and `X-Failed-Recipients` bounces. Permanent = 5.x.x status.

## Subdirectories

- **`Behaviors/`** — Mailers: `TNativeMailBehavior` (PHP `mail()`), `TPhpMailerBehavior` (PHPMailer, SMTP), `TDelayedEmailerBehavior`; `TAppEmailerBehavior` adds `createEmail()`/`sendEmail()` to the application.
- **`Cron/`** — `TSendEmailTask`, `TDbEmailSenderTask` (sends queued), `TDbEmailCleanerTask` (deletes old log rows, keeps pending), `TIMAPBouncedTask` (reads a bounce mailbox through `IEmailMailbox`).
- **`Mailbox/`** — `IEmailMailbox`; `TImapMailbox`, a socket IMAP reader (no ext-imap, which PHP 8.4 unbundled): LOGIN or XOAUTH2, SELECT, UID SEARCH UNSEEN, UID FETCH BODY.PEEK[], UID STORE \Seen. Transport is four protected methods, overridden in tests.
- **`Shell/`** — `TShellEmailerAction`: `email/send`, `log`, `show`, `flush`, `bounce` (reads stdin, for a mail server pipe).
- **`UI/`** — `TEmailPage` (TPage without request/response; passes unknown methods to its `TEmail`; `publishEmailFile()` by `TEmailPublishStyle` URL/Attachment/Embed; inlines CSS through `TEmailCssInliner`; rejects postback, callback, and active controls), `TEmailTemplate`, `TEmailPublishStyle`, `TEmailCssInliner`.
- **`UI/WebControls/`** — `TEmailTrackerImage`, `TEmailViewLink`, `TEmailTrackedLink`, `TEmailButton`, `TEmailUnsubscribeLink`, `TEmailPreheader`, `TEmailImage` (AlternateText required unless `Decorative`), `TEmailAttachment`; shared `TEmailControlTrait`.

## Conventions and gotchas

- Controls call `TEmail` methods on the page (`$page->addTrackedLink()`); `TEmailPage::getEmail()` renders the body, so controls never call it.
- Tracked links are stored per email and redirect by number; never redirect to a request parameter.
- Message-IDs and tracking IDs come from `random_bytes()`; the stored `message_id` is the local part, matched by `TDbEmailModule::normalizeMessageId()`.
- Unit tests use `tests/unit/Util/Email/TDbEmailModuleTestModule` (in-memory SQLite, captured sends).
