# HTTP_Response

Overview:
HTTP_Response is a simple container for response status, headers, and body, and casts to the response body as a string.

Use `HTTP_Response` to carry status, headers, and body as one object when integrating outbound HTTP calls or transport adapters.

Public API:
- `new HTTP_Response($contents, $status, $headers)`
- `__toString()` returns `contents`.
