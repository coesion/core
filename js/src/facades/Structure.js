const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function wrap(value) {
  if (value && typeof value === "object") return new Structure(value, true);
  return value;
}

class Structure extends ModuleSupport {
  constructor(input = {}, deep = true) {
    super();
    this.data = input;
    this.deep = deep;
    return new Proxy(this, {
      get: (target, prop) => {
        if (prop in target) return target[prop];
        return target.#read(prop);
      },
      set: (target, prop, value) => {
        target.data[prop] = value;
        return true;
      },
      has: (target, prop) => prop in target.data,
    });
  }

  #read(prop) {
    const value = this.data[prop];
    return this.deep ? wrap(value) : value;
  }

  static fetch(path, data, fallback = null) {
    const parts = String(path).split(".").filter(Boolean);
    let ref = data;
    for (const part of parts) {
      if (ref === null || ref === undefined || !(part in ref)) return fallback;
      ref = ref[part];
    }
    return JSON.parse(JSON.stringify(ref));
  }
}

registerClass("Structure", Structure);
module.exports = Structure;