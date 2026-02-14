const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Event = require("./Event");

class Core extends ModuleSupport {
  static VERSION = "0.1.0";

  static version() {
    return this.VERSION;
  }

  static diagnostics() {
    return {
      version: this.VERSION,
      node: process.version,
      platform: process.platform,
      arch: process.arch,
      pid: process.pid,
    };
  }

  static log(level, message, context = {}) {
    Event.trigger("core.log", level, message, context);
  }
}

registerClass("Core", Core);
module.exports = Core;