const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const MemoryCacheAdapter = require("../adapters/cache/MemoryCacheAdapter");
const FilesCacheAdapter = require("../adapters/cache/FilesCacheAdapter");

class Cache extends ModuleSupport {
  static #drivers = [];

  static using(input, options = {}) {
    const names = Array.isArray(input) ? input : [input];
    this.#drivers = names.map((name) => {
      if (typeof name === "object") return name;
      if (name === "memory") return new MemoryCacheAdapter(options.memory || {});
      if (name === "files") return new FilesCacheAdapter(options.files || options);
      throw new Error(`Unknown cache driver '${name}'`);
    });
    return this;
  }

  static #first() {
    if (!this.#drivers.length) this.using("memory");
    return this.#drivers[0];
  }

  static set(key, value, ttl = 0) {
    for (const driver of this.#drivers.length ? this.#drivers : [this.#first()]) {
      driver.set(key, value, ttl);
    }
    return value;
  }

  static get(key, fallback = undefined, ttl = 0) {
    const drivers = this.#drivers.length ? this.#drivers : [this.#first()];
    for (const driver of drivers) {
      const value = driver.get(key);
      if (value !== undefined) return value;
    }
    const resolved = typeof fallback === "function" ? fallback() : fallback;
    if (resolved !== undefined) this.set(key, resolved, ttl);
    return resolved;
  }

  static exists(key) {
    return this.#first().exists(key);
  }

  static delete(key) {
    for (const driver of this.#drivers.length ? this.#drivers : [this.#first()]) {
      driver.delete(key);
    }
  }

  static inc(key, by = 1) {
    return this.#first().inc(key, by);
  }

  static dec(key, by = 1) {
    return this.#first().dec(key, by);
  }
}

registerClass("Cache", Cache);
module.exports = Cache;