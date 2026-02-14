const test = require("node:test");
const assert = require("node:assert/strict");
const { Work, TaskCoroutine } = require("../src/index");

test("Work add parallel", () => {
  const results = [];
  Work.flush();

  Work.add(function* () {
    results.push("a");
    yield;
    results.push("b");
  });

  Work.add(function* () {
    results.push("c");
    yield;
    results.push("d");
  });

  Work.run();
  assert.deepEqual(results, ["a", "c", "b", "d"]);
});

test("TaskCoroutine lifecycle", () => {
  const gen = (function* () {
    const first = yield "start";
    yield first;
  })();

  const task = new TaskCoroutine(1, gen);
  assert.equal(task.run(), "start");
  assert.equal(task.complete(), false);

  task.pass("next");
  assert.equal(task.run(), "next");
  assert.equal(task.complete(), false);

  task.run();
  assert.equal(task.complete(), true);
});