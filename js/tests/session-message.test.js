const test = require("node:test");
const assert = require("node:assert/strict");
const { Session, Message } = require("../src/index");

test("Session set/get/delete", () => {
  Session.flush();
  Session.set("core_test_key", "value");
  assert.equal(Session.get("core_test_key"), "value");
  Session.delete("core_test_key");
  assert.equal(Session.exists("core_test_key"), false);
});

test("Message set/get clears", () => {
  Session.flush();
  Message.set("notice", "hello");
  assert.equal(Message.get("notice"), "hello");
  assert.equal(Message.get("notice"), "");
});