const test = require("node:test");
const assert = require("node:assert/strict");
const { Text } = require("../src/index");

test("Text render/basic", () => {
  assert.equal(Text.render("TEST:{{ a}}:{{b}}:{{c}}"), "TEST:::");
  assert.equal(Text.render("TEST:{{ a}}:{{ b   }}:{{c}}", { a: 1, b: 2 }), "TEST:1:2:");
  assert.equal(Text.render("TEST:{{a.x.y}}:{{b.x}}:{{a.b.x.y.u}}", { a: { x: { y: 1 } }, b: { x: 2 } }), "TEST:1:2:");
});

test("Text accent slug cut", () => {
  assert.equal(Text.slugify("This is --- a very wrong sentence!"), "this-is-a-very-wrong-sentence");
  const txt = "Name: Ethan Hunt; Role: Agent";
  assert.equal(Text.cut(txt, "Name: ", ";"), "Ethan Hunt");
  assert.equal(Text.cut(txt, "Name: "), "Ethan Hunt; Role: Agent");
});