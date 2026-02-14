const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const os = require("node:os");
const path = require("node:path");
const { i18n } = require("../src/index");

function reset() {
  i18n.flush();
  i18n.locale("en");
  i18n.fallback("en");
}

test("i18n locale/fallback", () => {
  reset();
  assert.equal(i18n.locale(), "en");
  i18n.locale("fr");
  assert.equal(i18n.locale(), "fr");
  i18n.fallback("de");
  assert.equal(i18n.fallback(), "de");
});

test("i18n translate dot keys and params", () => {
  reset();
  i18n.loadArray("en", { user: { welcome: "Hello, {{ name }}!" } });
  assert.equal(i18n.t("user.welcome", { name: "Alice" }), "Hello, Alice!");
  assert.equal(i18n.t("missing.key"), "missing.key");
});

test("i18n fallback locale", () => {
  reset();
  i18n.loadArray("en", { title: "Title EN" });
  i18n.loadArray("fr", { subtitle: "Sous-titre" });
  i18n.locale("fr");
  i18n.fallback("en");
  assert.equal(i18n.t("title"), "Title EN");
  assert.equal(i18n.t("subtitle"), "Sous-titre");
});

test("i18n load from json", () => {
  reset();
  const tmp = path.join(os.tmpdir(), `core_i18n_${Date.now()}.json`);
  fs.writeFileSync(tmp, JSON.stringify({ hello: "Hola" }), "utf8");
  i18n.load("es", tmp);
  i18n.locale("es");
  assert.equal(i18n.t("hello"), "Hola");
  fs.rmSync(tmp, { force: true });
});