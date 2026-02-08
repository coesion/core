# HTTP_Request

Overview:
HTTP_Request models a raw HTTP request for debugging or proxying purposes. It can be cast to a string to get a full request representation.

Public API:
- `new HTTP_Request($method, $url, $headers = [], $data = null)`
- `__toString()` returns a raw HTTP request string.


