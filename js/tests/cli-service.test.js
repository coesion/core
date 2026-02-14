const test = require("node:test");
const assert = require("node:assert/strict");
const { CLI, Service } = require("../src/index");

test("CLI register commands", () => {
  CLI.flush();
  CLI.on("hello :name", (name) => `Hi ${name}`, "Greets a user");
  const commands = CLI.commands();
  assert.equal(commands.length > 0, true);
  const match = commands.find((x) => x.name === "hello");
  assert.equal(!!match, true);
  assert.equal(match.description, "Greets a user");
  assert.equal(match.params.includes("[name]"), true);
});

test("Service container singleton and factory", () => {
  Service.flush();
  Service.register("email", () => "EMAIL SERVICE");
  assert.equal(Service.email() + Service.email(), "EMAIL SERVICEEMAIL SERVICE");

  Service.register("test", (data) => ({ data }));
  assert.equal(Service.test("--TEST--").data, "--TEST--");
  assert.equal(Service.test().data, "--TEST--");
  assert.equal(Service.test("NOT ME!").data, "--TEST--");

  Service.registerFactory("foos", (bar) => ({ data: bar }));
  assert.equal([Service.foos("A").data, Service.foos("B").data, Service.foos("C").data].join(""), "ABC");
});