const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Service extends ModuleSupport {
  static #singletons = new Map();
  static #factories = new Map();
  static #instances = new Map();

  static register(name, factory) {
    this.#singletons.set(name, factory);
    this[name] = (...args) => {
      if (this.#instances.has(name)) return this.#instances.get(name);
      const instance = factory(...args);
      this.#instances.set(name, instance);
      return instance;
    };
  }

  static registerFactory(name, factory) {
    this.#factories.set(name, factory);
    this[name] = (...args) => factory(...args);
  }

  static flush() {
    for (const name of [...this.#singletons.keys(), ...this.#factories.keys()]) delete this[name];
    this.#singletons.clear();
    this.#factories.clear();
    this.#instances.clear();
  }
}

registerClass("Service", Service);
module.exports = Service;