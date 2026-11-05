# Web/UI/WebControls/TIpAddressValidator

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TIpAddressValidator`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TIpAddressValidator.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TBaseValidator](./TBaseValidator.md)

## Overview
Validates an IPv4 or IPv6 address, optionally in CIDR notation, such as `192.0.2.1`, `2001:db8::1`, or `10.0.0.0/8`, and restricts it to ordered `Ranges`. Client and server apply the same checks. An empty value passes; pair it with [TRequiredFieldValidator](./TRequiredFieldValidator.md).

## Key Properties

| Property | Default | Description |
|---|---|---|
| `Version` | `Any` | [TIpAddressVersion](./TIpAddressVersion.md): `Any`, `IPv4`, or `IPv6`. |
| `AllowCidr` | `false` | The value may end in `/prefix`. |
| `RequireCidr` | `false` | The value must end in `/prefix`; implies `AllowCidr`. |
| `Ranges` | `''` | Comma separated, ordered entries; empty allows every address. `getRangeList()` resolves them and throws `TConfigurationException` (`ipaddressvalidator_range_invalid`) for an unknown entry. |

## Ranges

An entry is an address, a CIDR block, or a range name, case-insensitive, optionally prefixed with `!`. The first matching entry decides; a value no entry matches fails.

| Entry | Matches a value that | Result |
|---|---|---|
| plain | lies inside it | valid |
| `!` | shares any address with it | invalid |

| Name | Blocks |
|---|---|
| `private` | 10/8, 172.16/12, 192.168/16, fc00::/7 |
| `loopback` | 127/8, ::1/128 |
| `linklocal` | 169.254/16, fe80::/10 |
| `multicast` | 224/4, ff00::/8 |
| `documentation` | 192.0.2/24, 198.51.100/24, 203.0.113/24, 2001:db8::/32, 3fff::/20 |
| `reserved` | 0/8, 100.64/10, 192.0.0/24, 198.18/15, 240/4, ::/128, 64:ff9b:1::/48, 100::/64, 2001::/23, 5f00::/16, fec0::/10 |
| `any` | 0.0.0.0/0, ::/0 |
| `public` | every address outside the named ranges above except `any` (`Outside` entry) |

Examples: `public`; `private`; `!public, any`; `!10.1.0.0/16, 10.0.0.0/8`.

## Mechanics

- `NAMED_RANGES` holds the table; the blocks come from the IANA special-purpose registries. The few globally reachable exceptions inside 192.0.0.0/24 and 2001::/23 count as `reserved`. `::ffff:0:0/96` is in no named range.
- An IPv4-mapped IPv6 value (`::ffff:a.b.c.d`, or a block of prefix 96 or more) also matches IPv4 blocks through its embedded address, so `::ffff:127.0.0.1` is `loopback` and not `public`.
- Syntax: server `filter_var(FILTER_VALIDATE_IP)`; client `Prado.Validation.isIPv4()` / `isIPv6()`. No leading zeros in IPv4, no zone (`%eth0`), no brackets, no surrounding spaces. A prefix has no leading zeros and is at most 32 or 128. A range block may have host bits set; they are ignored.
- Matching: server `inet_pton()` and `prefixMatches()`; client `Prado.Validation.ipAddressBytes()` and `prefixMatches()`.
- `isValidIpAddress(string): bool` and `prefixMatches()` (static) are public.
- Client options: `Version`, `AllowCidr`, `RequireCidr`, and `Ranges` as `getRangeList()` entries (`Negate`, `Blocks`, `Outside`), so the range table lives only on the server.

## Accessibility
Inherited from TBaseValidator: the message has `role="alert"`, it is linked to the field through `aria-describedby`, and the field gets `aria-invalid`. An error message that names the expected form, such as "Enter a public IPv4 or IPv6 address", tells the user what to change.

## Tests

- `tests/unit/Web/UI/WebControls/TIpAddressValidatorTest.php`
- `tests/js/validator/ipaddressvalidator.test.js`
- Both run the cases in `tests/js/validator/ip-cases.json`. Its `clientRanges` are the resolved `Ranges` of each option set; the PHP test checks them against `getRangeList()`.
- `tests/playwright/validators/IpAddressValidatorTestCase.spec.js` with `tests/harness/validators/protected/pages/IpAddressValidator.page`

## See Also

- [TBaseValidator](./TBaseValidator.md)
- [TUrlValidator](./TUrlValidator.md)

**@since 4.4.0**
