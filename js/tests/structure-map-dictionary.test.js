const test = require("node:test");
const assert = require("node:assert/strict");
const { Dictionary, Map, Structure } = require("../src/index");

class TestDict extends Dictionary {}

test("Dictionary core behavior", () => {
  TestDict.clear();
  TestDict.load({ a: 123, b: "hello", c: { r: "#f00" }, this: { must: { be: false } } });
  assert.equal(TestDict.get("a"), 123);
  assert.equal(TestDict.exists("not-a"), false);
  TestDict.set("a.b.c.d", 1);
  assert.equal(TestDict.get("a.b.c.d", 0), 1);
  assert.equal(TestDict.get("this.must.be"), false);
  const res = TestDict.get({ X: "a.b.c.d", Y: "b" });
  assert.equal(res.X, 1);
  assert.equal(res.Y, "hello");
});

test("Map behavior", () => {
  const map = new Map();
  map.load({ a: 1, c: { r: "#f00" } });
  map.set("a", 999);
  assert.equal(map.get("a"), 999);
  map.merge({ a: 123, b: 2 }, true);
  assert.equal(map.get("a"), 999);
  map.merge({ a: 123 }, false);
  assert.equal(map.get("a"), 123);
});

test("Structure fetch", () => {
  const data = { a: { x: { y: 1 } }, b: { x: 2 } };
  assert.equal(Structure.fetch("a.x.y", data), 1);
  assert.deepEqual(Structure.fetch("a.x", data), { y: 1 });
});