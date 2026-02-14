const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const { Cache } = require("../src/index");

test("Cache set/get memory", () => {
  Cache.using("memory");
  Cache.set("test", "ALPHA");
  assert.equal(Cache.get("test"), "ALPHA");
});

test("Cache get unknown with fallback", () => {
  Cache.using("memory");
  assert.equal(Cache.get("test2", "BETA"), "BETA");
  assert.equal(Cache.get("test2"), "BETA");
});

test("Cache fallback closure", () => {
  Cache.using("memory");
  assert.equal(Cache.get("test3", () => "SLOW_DATA :)"), "SLOW_DATA :)");
});

test("Files cache basic operations", () => {
  const dir = path.join(process.cwd(), `.cache-test-${Date.now()}`);
  Cache.using("files", { cache_dir: dir });
  Cache.set("alpha", "beta");
  assert.equal(Cache.exists("alpha"), true);
  assert.equal(Cache.get("alpha"), "beta");
  Cache.delete("alpha");
  assert.equal(Cache.exists("alpha"), false);
  fs.rmSync(dir, { recursive: true, force: true });
});