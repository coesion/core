const { registerClass } = require("../kernel/registry");
const { deepGet, deepSet, merge } = require("../kernel/deep");

class Dictionary {
  static _fields = {};

  static clear() { this._fields = {}; }
  static load(data = {}) { this._fields = JSON.parse(JSON.stringify(data)); }
  static all() { return JSON.parse(JSON.stringify(this._fields || {})); }
  static set(path, value) { return deepSet(this._fields, path, value); }
  static exists(path) { return deepGet(this._fields, path, undefined) !== undefined; }
  static merge(data, left = false) { this._fields = merge(this._fields, data, !!left); }

  static get(path, fallback = null) {
    if (path && typeof path === "object" && !Array.isArray(path)) {
      const out = {};
      for (const [k, p] of Object.entries(path)) out[k] = this.get(p, fallback);
      return out;
    }
    const val = deepGet(this._fields, path, undefined);
    if (val !== undefined) return val;
    return typeof fallback === "function" ? fallback() : fallback;
  }
}

registerClass("Dictionary", Dictionary);
module.exports = Dictionary;