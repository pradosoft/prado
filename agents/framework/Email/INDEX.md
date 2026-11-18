# Email/INDEX.md

### Directories
[framework](../INDEX.md) / **`Email`**

## Purpose

Email subsystem (issue #760, since 4.4.0), namespace `Prado\Email`. One message object, a template body that renders anywhere, transports behind an interface, and a store that logs, queues, tracks, and suppresses.

## Flow

```
$app->createEmail('Order.Shipped')        TEmailModule builds a TEmail whose Body is a TEmailBody
$email->send()                            TEmailModule::sendEmail():
   render         Body sets Html/Text (placeholders stay)
   defaults       module From / ReplyTo / ReturnPath fill gaps
   suppression    store removes suppressed recipients -> TEmailStatus::Suppressed when none is left
   copies         module Cc / Bcc are added
   finalize       placeholders replaced, Message-ID assigned
   onSendEmail    a handler may change or cancel the email
   SendMode       Immediate -> transport now | Deferred -> end of request | Queued -> cron
   onEmailSent / onEmailFailed
```

## Classes

- **`TEmail`** — The message: `From`, `Sender`, `ReturnPath`, `To`/`Cc`/`Bcc`/`ReplyTo` (`TEmailAddress[]`), `Subject`, `Html`, `Text`, `Body` (`IEmailBody`), `Files` (`attach()`, `attachData()`, `embed()`), `Data` (placeholder values), `List`, `Secure`, `TrackingId`, `MessageId`, tracked links, additional headers (`setHeader()`, `setListUnsubscribe()`), `Status`, `Error`. `render()` runs the Body once; `finalize()` replaces placeholders once. `__clone` gives a new TrackingId and MessageId.
- **`TEmailAddress`** — Immutable mailbox, validated on construction; `parse()`, `parseList()` (quote and angle aware), `format()`. IDN domains become ASCII; a non-ASCII local part is rejected.
- **`TEmailFile`** — Immutable attachment or inline file (`withContentId()`); `toArray()`/`fromArray()` for storage.
- **`TEmailHtmlToText`** — Derives the Text from the Html on DOM.
- **`TEmailModule`** — `createEmail()`, `sendEmail()`, `sendEach()`, `sendDeferred()`, `sendQueued()`, `processBounceMessage()`, `recordOpen/Click/Unsubscribe()`, `getTrackingUrl()`. Loads `<transport>` (one), `<emails>`, and `<service>` children. `StoreID` names the `IEmailStore` module. Offers the cron tasks through `fxGetCronTaskInfos` and the shell action through `dyRegisterShellAction`.
- **`TEmailConfiguration`** — `<emails>` property rules by email path pattern (`Help/*`); a name is a sub-property path of the body (`MasterClass`, `Email.From`).
- **`TEmailService`** — Serves the tracking ID: `open` (GIF), `click=N` (redirects only to a registered http/https link), `unsubscribe` (GET confirms, POST acts, RFC 8058), or shows the email with a sandboxing CSP.
- **`TEmailEventParameter`**, **`TEmailTrackingEventParameter`** — Parameters of the sending events (stoppable; `cancel()`) and of `onOpenEmail`, `onClickEmail`, `onUnsubscribe`, `onBounce`.
- **`TEmailStatus`**, **`TEmailSendMode`** — Enumerables.
- **`IEmailBody`**, **`IEmailMailer`** — What `TEmail` needs from a body and from the module.

## Subdirectories

- **`Mime/`** — `TEmailMimeBuilder`: the one writer of RFC 5322 messages. `alternative(text, related(html, inline files))` inside `mixed` with attachments; quoted-printable text, base64 files, RFC 2047 headers, folding at 78.
- **`Net/`** — `TEmailSocketConnection`: CRLF line protocol on `Prado\IO\Socket\TSocketStream` with STARTTLS; every failure is a `TNetworkException`. `TEmailEncryption` enumerable.
- **`Transport/`** — `IEmailTransport` (`send()` throws `TEmailTransportException`, `close()`); `TSmtpTransport` (EHLO, STARTTLS, AUTH PLAIN/LOGIN/XOAUTH2, RSET reuse; `TSmtpException` with the reply code), `TMailTransport` (PHP `mail()`, the default), `TFileTransport` (`.eml` files for development).
- **`Store/`** — `IEmailStore`; `TDbEmailStore` (module on `TDbModule`): tables `email_messages`, `email_recipients`, `email_suppressions`; queue claims by one UPDATE; retries with doubling delay up to `MaxAttempts`; stale claims return after `ClaimTimeout`. `TEmailSuppressionReason` enumerable.
- **`Bounce/`** — `TEmailBounce`, `TEmailBounceParser` (RFC 3464 DSNs and `X-Failed-Recipients`).
- **`Mailbox/`** — `IEmailMailbox`; `TImapMailbox`, a socket IMAP reader for the bounce mailbox (no ext-imap).
- **`Cron/`** — `TEmailQueueTask`, `TEmailCleanupTask`, `TEmailBounceTask` (base `TEmailTask` finds the module).
- **`Shell/`** — `TShellEmailAction`: `email/send`, `log`, `show`, `flush`, `bounce` (reads stdin), `test`.
- **`UI/`** — `TEmailBody` (a `TPage` to its controls; renders without a request or a page service; `PublishStyle`, `AbsoluteUrl`, `InlineStyles`), `TEmailBodyTemplate` (splits the directive into body and `Email.` properties), `TEmailCssInliner`, `TEmailPublishStyle`.
- **`UI/WebControls/`** — `TEmailTrackerImage`, `TEmailViewLink`, `TEmailTrackedLink`, `TEmailButton`, `TEmailUnsubscribeLink`, `TEmailPreheader`, `TEmailImage`, `TEmailAttachment`; shared `TEmailControlTrait`.

## Conventions and gotchas

- **Names:** `TEmail` is the message; `TEmailBody` is the template control; `getBody()` returns the body object; the content strings are `getHtml()` and `getText()`.
- **Precedence of email properties:** `<emails>` rules (at creation) < template `Email.` directive (at creation) < application code before `send()` < body code during render. Module `From`/`ReplyTo`/`ReturnPath` only fill gaps.
- **Placeholders:** `{{KEY}}` is HTML encoded in Html, raw in Text, Subject, and headers, URL encoded in tracked links; `{{{KEY}}}` is raw. A value is not searched again, so user-written text enters an email as a Data value.
- **Tracking URLs** hold `{{email:tracking-id}}` until `finalize()`. A template rendered once and sent with `sendEach()` gives each recipient their own URLs, TrackingId, and Message-ID.
- **One email, one status:** an email that was sent cannot be sent again; send a clone.
- **Suppression:** an unsubscribe suppresses (address, `List`) and needs one To address; an email without a `List` is transactional and ignores unsubscribes. A permanent bounce suppresses the address for every email. A bounce only counts when its Message-ID and recipient match a stored email.
- **Outside a web request** (cron, shell) the module needs `ServiceUrl` for tracking URLs and the body needs `AbsoluteUrl` for the URL publish style.
- **The service's own pages** encode with `htmlspecialchars`; `THttpUtility::htmlEncode` leaves "&" as it is.
- **Tests:** `tests/unit/Email/`. `Net/TEmailTestServer` scripts a server over `TSocketStream::pair()`; `Store/TSqliteEmailStore` is the store on in-memory SQLite with a mock clock; `Transport/TCaptureTransport` keeps what is sent. A test that creates a `TEmailModule` uses `TEmailModuleCleanupTrait`: the module listens to global events and would answer the `fx` events of later tests.
- **Not built:** DKIM signing, SMTPUTF8, provider webhooks, a wake of cron when an email is queued (`TEmailModule::signalQueue()` is the seam).
