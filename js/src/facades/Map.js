const { registerClass } = require("../kernel/registry");
const { deepGet, deepSet, merge } = require("../kernel/deep");

class Map {
  constructor() {
    this.fields = {};
  }

  all() { return JSON.parse(JSON.stringify(this.fields)); }
  load(data = {}) { this.fields = JSON.parse(JSON.stringify(data)); }
  clear() { this.fields = {}; }
  set(path, value) { return deepSet(this.fields, path, value); }
  get(path, fallback = null) {
    const val = deepGet(this.fields, path, undefined);
    if (val !== undefined) return val;
    return typeof fallback === "function" ? fallback() : fallback;
  }
  exists(path) { return deepGet(this.fields, path, undefined) !== undefined; }
  merge(data, left = false) { this.fields = merge(this.fields, data, !!left); }
}

registerClass("Map", Map);
module.exports = Map;