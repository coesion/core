const test = require("node:test");
const assert = require("node:assert/strict");
const { URL } = require("../src/index");

test("URL parse/build", () => {
  const original = "https://user:pass@www.alpha.beta.com:9080/path/to/resource.html";
  const url = new URL(original);
  assert.equal(String(url), original);

  const next = new URL();
  next.scheme = "ftps";
  next.host = "test.com";
  next.port = 9000;
  next.path = "index.php";
  assert.equal(String(next), "ftps://test.com:9000/index.php");
});

test("URL query", () => {
  const url = new URL("https://user:pass@www.alpha.beta.com:9080/path/to/resource.html?query=string&another[]=2&another[]=3#fragment");
  assert.equal(url.query.query, "string");

  const u = new URL();
  u.query.alpha = 123;
  u.query.beta = { a: 1, b: 2 };
  assert.equal(decodeURIComponent(String(u)), "?alpha=123&beta[a]=1&beta[b]=2");
});