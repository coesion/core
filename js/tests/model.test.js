const test = require("node:test");
const assert = require("node:assert/strict");
const { SQL, Schema, Model, MemoryAdapter } = require("../src/index");

class User extends Model {}

function setup() {
  const adapter = new MemoryAdapter();
  adapter.createTable("users", ["id", "name", "email", "active"], "id");
  SQL.connect(adapter);
  User.persistOn("users");
}

test("Schema introspection through SQL adapter", () => {
  setup();
  assert.deepEqual(Schema.tables(), ["users"]);
  assert.equal(Schema.describe("users").length, 4);
});

test("Model CRUD-style operations", () => {
  setup();

  const user = User.create({ name: "Alice", email: "a@b.com", active: 1 });
  assert.equal(user.id, 1);

  const loaded = User.load(1);
  assert.equal(loaded.name, "Alice");

  loaded.name = "Bob";
  loaded.save();

  const rows = User.where("active = ?", [1]);
  assert.equal(rows.length, 1);
  assert.equal(rows[0].name, "Bob");
  assert.equal(User.count("active = ?", [1]), 1);
});