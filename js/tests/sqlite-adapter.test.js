const test = require("node:test");
const assert = require("node:assert/strict");
const { SQL } = require("../src/index");

test("SQLite adapter availability and basic flow", () => {
  const adapter = SQL.connect("sqlite::memory:");
  if (!adapter.enabled) {
    assert.equal(typeof adapter.enabled, "boolean");
    return;
  }

  SQL.exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)");
  SQL.query("INSERT INTO users (name) VALUES (?)", ["Alice"]);
  const rows = SQL.query("SELECT * FROM users");
  assert.equal(rows.length, 1);
  assert.equal(rows[0].name, "Alice");
});