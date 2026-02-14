const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Session extends ModuleSupport {
  static #store = new Map();
  static #name = "core.sid";

  static set(key, value) {
    this.#store.set(String(key), value);
    return value;
  }

  static get(key, fallback = "") {
    return this.#store.has(String(key)) ? this.#store.get(String(key)) : fallback;
  }

  static delete(key) {
    this.#store.delete(String(key));
  }

  static exists(key) {
    return this.#store.has(String(key));
  }

  static flush() {
    this.#store.clear();
  }

  static name(value = null) {
    if (value !== null) this.#name = String(value);
    return this.#name;
  }
}

registerClass("Session", Session);
module.exports = Session;