const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function deepMerge(target, source) {
  const out = { ...target };
  for (const [k, v] of Object.entries(source || {})) {
    if (v && typeof v === "object" && !Array.isArray(v) && out[k] && typeof out[k] === "object" && !Array.isArray(out[k])) {
      out[k] = deepMerge(out[k], v);
    } else {
      out[k] = v;
    }
  }
  return out;
}

function getByDot(input, key) {
  return String(key).split(".").reduce((acc, part) => (acc && part in acc ? acc[part] : undefined), input);
}

class i18n extends ModuleSupport {
  static #dict = {};
  static #locale = "en";
  static #fallback = "en";

  static locale(value = null) {
    if (value !== null) this.#locale = value;
    return this.#locale;
  }

  static fallback(value = null) {
    if (value !== null) this.#fallback = value;
    return this.#fallback;
  }

  static load(locale, filePath) {
    const ext = path.extname(filePath).toLowerCase();
    if (ext === ".json") {
      this.loadArray(locale, JSON.parse(fs.readFileSync(filePath, "utf8")));
      return;
    }
    if (ext === ".php") {
      const raw = fs.readFileSync(filePath, "utf8");
      const match = raw.match(/\{[\s\S]*\}/);
      if (!match) return;
      const obj = JSON.parse(match[0].replace(/=>/g, ":").replace(/'/g, '"'));
      this.loadArray(locale, obj);
    }
  }

  static loadArray(locale, data) {
    this.#dict[locale] = deepMerge(this.#dict[locale] || {}, data || {});
  }

  static t(key, params = {}) {
    let value = getByDot(this.#dict[this.#locale] || {}, key);
    if (value === undefined) value = getByDot(this.#dict[this.#fallback] || {}, key);
    if (value === undefined) return key;
    return String(value).replace(/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/g, (_, name) => (params[name] ?? ""));
  }

  static has(key) {
    return this.t(key) !== key;
  }

  static all(locale = null) {
    return { ...(this.#dict[locale || this.#locale] || {}) };
  }

  static flush() {
    this.#dict = {};
    this.#locale = "en";
    this.#fallback = "en";
  }
}

registerClass("i18n", i18n);
module.exports = i18n;