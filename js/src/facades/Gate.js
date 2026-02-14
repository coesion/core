const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Auth = require("./Auth").Auth;

class Gate extends ModuleSupport {
  static #policies = new Map();

  static define(name, callback) {
    this.#policies.set(name, callback);
  }

  static allows(name, ...args) {
    const policy = this.#policies.get(name);
    if (!policy) return false;
    return !!policy(Auth.user(), ...args);
  }

  static denies(name, ...args) {
    return !this.allows(name, ...args);
  }

  static flush() {
    this.#policies.clear();
  }
}

registerClass("Gate", Gate);
module.exports = Gate;