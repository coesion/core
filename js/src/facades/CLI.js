const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class CLI extends ModuleSupport {
  static #commands = [];

  static on(pattern, callback, description = "") {
    const parts = String(pattern).trim().split(/\s+/);
    const name = parts[0];
    const params = parts.slice(1).map((p) => p.replace(/^:/, "[").concat("]")).join(" ");
    this.#commands.push({ name, pattern, callback, description, params });
  }

  static commands() {
    return this.#commands.map(({ callback, ...rest }) => ({ ...rest }));
  }

  static flush() {
    this.#commands = [];
  }
}

registerClass("CLI", CLI);
module.exports = CLI;