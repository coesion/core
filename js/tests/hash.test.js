const test = require("node:test");
const assert = require("node:assert/strict");
const { Hash } = require("../src/index");

test("Hash algos and verify", () => {
  const algos = Hash.methods();
  assert.equal(algos.length > 0, true);
  const algo = algos[0];
  assert.equal(Hash.can(algo), true);
  const payload = [1,2,3];
  const hash = Hash.make(payload, algo);
  assert.equal(!!hash, true);
  assert.equal(Hash.verify(payload, hash, algo), true);
});

test("Hash uuid murmur random", () => {
  const u = Hash.uuid();
  assert.notEqual(u, Hash.uuid());
  assert.equal(Hash.uuid(3, "not-valid!", "123"), false);
  assert.equal(Hash.murmur("Hello World", 0, true), 427197390);
  assert.notEqual(Hash.random(), Hash.random());
});