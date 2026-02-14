const test = require("node:test");
const assert = require("node:assert/strict");
const { Password } = require("../src/index");

test("Password make/verify/compare", () => {
  const p = "MyLittleSecret";
  const hash = Password.make(p);
  const hash2 = Password.make("Fake!");
  assert.equal(Password.verify(p, hash), true);
  assert.equal(Password.verify(p, hash2), false);
  assert.equal(Password.compare(p, p), true);
  assert.equal(Password.compare(p, "not-the-same"), false);
});