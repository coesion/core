const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Filter extends ModuleSupport {
  static #filters = new Map();

  static add(name, callback) {
    if (!this.#filters.has(name)) this.#filters.set(name, []);
    this.#filters.get(name).push(callback);
    return callback;
  }

  static remove(name, callback = null) {
    if (!this.#filters.has(name)) return;
    if (!callback) {
      this.#filters.delete(name);
      return;
    }
    const next = this.#filters.get(name).filter((fn) => fn !== callback);
    if (next.length) this.#filters.set(name, next);
    else this.#filters.delete(name);
  }

  static apply(name, value, ...args) {
    const handlers = this.#filters.get(name) || [];
    return handlers.reduce((acc, fn) => fn(acc, ...args), value);
  }

  static has(name) {
    return (this.#filters.get(name) || []).length > 0;
  }

  static reset() {
    this.#filters.clear();
  }
}

registerClass("Filter", Filter);
module.exports = Filter;