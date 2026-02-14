const path = require("node:path");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Loader extends ModuleSupport {
  static #paths = [];

  static addPath(dirPath, name = null) {
    this.#paths.push({ path: dirPath, name });
  }

  static paths() {
    return [...this.#paths];
  }

  static register() {
    return true;
  }

  static resolve(className) {
    const normalized = className.replace(/\\/g, "/");
    const candidates = [
      `${normalized}.js`,
      `${normalized.replace(/_/g, "/")}.js`,
    ];
    for (const entry of this.#paths) {
      for (const candidate of candidates) {
        const full = path.join(entry.path, candidate);
        try {
          return require(full);
        } catch {
          // keep searching
        }
      }
    }
    return null;
  }
}

Loader.addPath(path.join(__dirname));
registerClass("Loader", Loader);
module.exports = Loader;