const os = require("node:os");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Event = require("./Event");

class Errors extends ModuleSupport {
  static JSON = "json";
  static JSON_VERBOSE = "json_verbose";
  static TEXT = "text";

  static #mode = Errors.TEXT;
  static #captured = false;

  static mode(nextMode = null) {
    if (nextMode !== null) this.#mode = nextMode;
    return this.#mode;
  }

  static capture() {
    if (this.#captured) return;
    this.#captured = true;
    process.on("uncaughtException", (error) => {
      this.report(error);
    });
    process.on("unhandledRejection", (error) => {
      this.report(error instanceof Error ? error : new Error(String(error)));
    });
  }

  static report(error) {
    const payload = {
      error: error.message,
      type: error.name,
      code: error.code || 0,
    };
    if (this.#mode === Errors.JSON_VERBOSE) {
      payload.file = error.fileName || "";
      payload.line = Number(error.lineNumber || 0);
      payload.trace = String(error.stack || "").split(os.EOL).filter(Boolean);
    }
    const output = this.#mode.startsWith("json") ? JSON.stringify(payload) : `${payload.type}: ${payload.error}`;
    Event.trigger("error", payload);
    return output;
  }
}

registerClass("Errors", Errors);
module.exports = Errors;