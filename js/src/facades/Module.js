const { registerExtension } = require("../kernel/registry");

class ModuleSupport {
  static __extensions = new Map();

  static extend(method, callback = null) {
    const methods = callback === null && typeof method === "object" ? method : { [method]: callback };
    for (const [name, fn] of Object.entries(methods)) {
      if (typeof fn !== "function") {
        throw new TypeError(`Extension '${name}' must be a function`);
      }
      this.__extensions.set(name, fn);
      this[name] = (...args) => fn.apply(this, args);
      registerExtension(this.name, name);
    }
  }
}

module.exports = ModuleSupport;