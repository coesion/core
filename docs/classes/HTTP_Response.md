# HTTP_Response

Overview:
HTTP_Response is a simple container for response status, headers, and body, and casts to the response body as a string.

Public API:
- `new HTTP_Response($contents, $status, $headers)`
- `__toString()` returns `contents`.


