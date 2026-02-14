const test = require("node:test");
const assert = require("node:assert/strict");
const os = require("node:os");
const path = require("node:path");
const fs = require("node:fs");
const { File } = require("../src/index");

test("File mounts read/write/search/append", () => {
  const root = path.join(os.tmpdir(), `core-file-${Date.now()}`);
  fs.mkdirSync(root, { recursive: true });

  File.mount("temp", "native", { root });
  File.mount("mem", "memory");

  assert.deepEqual(File.mounts(), ["temp", "mem"]);

  File.write("mem://my/file.txt", "Hello World!");
  assert.equal(File.exists("mem://my/file.txt"), true);
  assert.equal(File.read("mem://my/file.txt"), "Hello World!");

  File.append("mem://my/file.txt", "!");
  assert.equal(File.read("mem://my/file.txt"), "Hello World!!");

  File.write("temp://core-test.txt", "TESTIFICATE");
  assert.equal(File.read("core-test.txt"), "TESTIFICATE");
  assert.equal(File.search("*.txt").includes("temp://core-test.txt"), true);

  fs.rmSync(path.join(root, "core-test.txt"), { force: true });
  fs.rmSync(root, { recursive: true, force: true });
});
