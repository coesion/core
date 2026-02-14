const test = require("node:test");
const assert = require("node:assert/strict");
const { Options, Filter, Event } = require("../src/index");

test("Options set/get and merge", () => {
  Options.reset();
  Options.set("core.response.autosend", false);
  assert.equal(Options.get("core.response.autosend"), false);

  Options.merge({ core: { route: { loop_mode: true } } });
  assert.equal(Options.get("core.route.loop_mode"), true);
});

test("Options loadArray applies filters and emits loaded event", () => {
  Options.reset();
  Filter.reset();
  Event.reset();

  Filter.add("load", (input) => ({ ...input, y: 2 }));
  Filter.add("load.array", (input) => ({ ...input, z: 3 }));

  let fired = false;
  Event.on("options.loaded", () => {
    fired = true;
  });

  Options.loadArray({ x: 1 });
  assert.deepEqual(Options.all(), { x: 1, y: 2, z: 3 });
  assert.equal(fired, true);
});