const test = require("node:test");
const assert = require("node:assert/strict");
const { Schedule } = require("../src/index");

function ts(y, m, d, hh, mm) {
  return new Date(Date.UTC(y, m - 1, d, hh, mm, 0)).getTime();
}

test("Schedule register/all/unregister", () => {
  Schedule.flush();
  Schedule.register("test", "* * * * *", "test.type");
  const all = Schedule.all();
  assert.equal(!!all.test, true);
  assert.equal(all.test.type, "test.type");
  Schedule.unregister("test");
  assert.deepEqual(Schedule.all(), {});
});

test("Schedule cron matching", () => {
  Schedule.flush();
  assert.equal(Schedule.matches("* * * * *", Date.now()), true);
  assert.equal(Schedule.matches("30 * * * *", ts(2025, 6, 15, 12, 30)), true);
  assert.equal(Schedule.matches("15 * * * *", ts(2025, 6, 15, 12, 30)), false);
  assert.equal(Schedule.matches("*/5 * * * *", ts(2025, 6, 15, 12, 15)), true);
  assert.equal(Schedule.matches("0-10/2 * * * *", ts(2025, 6, 15, 12, 3)), false);
  assert.equal(Schedule.matches("invalid", Date.now()), false);
});

test("Schedule due", () => {
  Schedule.flush();
  const t = ts(2025, 6, 15, 14, 30);
  Schedule.register("always", "* * * * *", "job.always");
  Schedule.register("never", "0 0 1 1 *", "job.never");
  Schedule.register("exact", "30 14 * * *", "job.exact");
  const due = Schedule.due(t).map((x) => x.name);
  assert.equal(due.includes("always"), true);
  assert.equal(due.includes("exact"), true);
  assert.equal(due.includes("never"), false);
});