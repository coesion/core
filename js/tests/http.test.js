const test = require("node:test");
const assert = require("node:assert/strict");
const { HTTP, HTTP_Request, HTTP_Response } = require("../src/index");

test("HTTP headers and ua", () => {
  HTTP.addHeader("X-Test", "1");
  assert.equal(HTTP.headers("X-Test"), "1");
  HTTP.removeHeader("X-Test");
  assert.equal(HTTP.headers("X-Test"), "");
  HTTP.userAgent("CoreTestUA");
  assert.equal(HTTP.userAgent(), "CoreTestUA");
  HTTP.proxy("127.0.0.1:8888");
  assert.equal(HTTP.proxy(), "127.0.0.1:8888");
});

test("HTTP response header parsing and request cast", () => {
  HTTP.setLastResponseHeader("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nX-Test: abc\r\n");
  const headers = HTTP.lastResponseHeader();
  assert.equal(headers["Content-Type"][0], "text/plain");
  assert.equal(headers["X-Test"][0], "abc");

  const res = new HTTP_Response("body", 200, { X: "1" });
  assert.equal(String(res), "body");

  const req = new HTTP_Request("post", "example.com/path?x=1", { "Content-Type": "application/json" }, { a: 1 });
  const raw = String(req);
  assert.equal(raw.startsWith("POST /path?x=1 HTTP/1.1\r\n"), true);
  assert.equal(raw.includes("Host: example.com\r\n"), true);
  assert.equal(raw.includes("\r\n\r\n{\"a\":1}"), true);
});