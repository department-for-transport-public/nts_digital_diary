# Decision record for UTF-8 Request Sanitization

Title: Handling invalid UTF-8 in incoming HTTP requests

## Context and Problem Statement

All translation files in this project use ICU message formatting (`+intl-icu`). PHP's `MessageFormatter::format()` validates ALL argument values for UTF-8 encoding, including parameters not referenced in the message pattern. When Symfony's built-in constraints (e.g. `Email`, `NotBlank`) build validation violations, they include the submitted value as a `{{ value }}` parameter. If this value contains invalid UTF-8 byte sequences, the IntlFormatter throws an unrecoverable `InvalidArgumentException`:

```
Unable to format message (error #10): Invalid UTF-8 data in string argument: ... U_INVALID_CHAR_FOUND
```

This was discovered when bots/attackers began POSTing garbage data to the `/feedback` endpoint, but the vulnerability affects every form endpoint in the application.

## Decision

1. A `SanitizeUtf8Subscriber` kernel event subscriber runs at priority 200 on `kernel.request`
2. For query string and POST parameters: any individual string value containing invalid UTF-8 is replaced with `null`. Valid sibling values are preserved
3. For raw request body content (JSON API endpoints): the request is rejected with a 400 Bad Request, since the raw body cannot be replaced in-place via Symfony's public API

## Rationale

- **Sanitize rather than reject (for form parameters):** Returning a 400 for invalid UTF-8 gives attackers a distinguishing signal to probe against. By sanitizing and letting the request continue, attackers see the same validation errors as any other bad form submission
- **Null out the entire value rather than stripping invalid bytes:** Stripping bytes could leave plausible-looking data (e.g. `"J\x80ohn"` becomes `"John"`) that passes validation and gets persisted. Nulling ensures validators like `NotBlank` fire, producing normal validation errors
- **Preserve valid sibling fields:** Only the offending field is nulled, so a form with one bad field and several valid fields gets the expected per-field validation behaviour
- **Reject raw body content with 400:** JSON is required to be UTF-8 by specification, so rejecting malformed JSON bodies is standard and expected behaviour. There is no information leakage since any JSON client would expect this
- **Legitimate users are unaffected:** Modern browsers always encode form data as UTF-8 when the page charset is UTF-8

## Implementation

- `src/EventSubscriber/SanitizeUtf8Subscriber.php` subscribes to `KernelEvents::REQUEST` at priority 200
- Runs after Symfony's `ValidateRequestListener` (256) and before `RouterListener` (32)
- Only processes main requests (sub-requests are ignored)
- Uses `mb_check_encoding($value, 'UTF-8')` for validation
- Logs a warning with request method, URI, and client IP when sanitization occurs

## Expected outcomes

1. The IntlFormatter crash is eliminated for all form endpoints
2. Attackers receive no useful signal from their probing
3. No garbage data can be persisted through form submissions
4. Legitimate form submissions are completely unaffected
