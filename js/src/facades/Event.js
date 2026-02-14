const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Event extends ModuleSupport {
  static #events = new Map();

  static on(name, callback) {
    if (!this.#events.has(name)) this.#events.set(name, []);
    this.#events.get(name).push(callback);
    return callback;
  }

  static off(name, callback = null) {
    if (!this.#events.has(name)) return;
    if (!callback) {
      this.#events.delete(name);
      return;
    }
    const next = this.#events.get(name).filter((fn) => fn !== callback);
    if (next.length) this.#events.set(name, next);
    else this.#events.delete(name);
  }

  static trigger(name, ...args) {
    const handlers = this.#events.get(name) || [];
    for (const handler of handlers) handler(...args);
  }

  static has(name) {
    return (this.#events.get(name) || []).length > 0;
  }

  static reset() {
    this.#events.clear();
  }
}

registerClass("Event", Event);
module.exports = Event;