const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Response extends ModuleSupport {
  static #status = 200;
  static #body = "";
  static #headers = new Map();

  static clean() {
    this.#status = 200;
    this.#body = "";
    this.#headers = new Map();
  }

  static status(code = null) {
    if (code !== null) this.#status = Number(code);
    return this.#status;
  }

  static write(chunk) {
    this.#body += String(chunk ?? "");
  }

  static body(value = undefined) {
    if (value !== undefined) this.#body = String(value ?? "");
    return this.#body;
  }

  static json(value, code = 200) {
    this.status(code);
    this.header("Content-Type", "application/json");
    this.body(JSON.stringify(value));
  }

  static text(value, code = 200) {
    this.status(code);
    this.header("Content-Type", "text/plain; charset=utf-8");
    this.body(value);
  }

  static header(name, value, append = true) {
    const key = String(name);
    if (!this.#headers.has(key) || !append) this.#headers.set(key, []);
    this.#headers.get(key).push([String(value)]);
  }

  static push(resource, as = "text") {
    if (Array.isArray(resource)) {
      for (const entry of resource) this.push(entry, as);
      return;
    }
    if (resource && typeof resource === "object") {
      for (const [kind, val] of Object.entries(resource)) {
        const type = Number.isInteger(Number(kind)) ? "text" : kind;
        this.push(val, type);
      }
      return;
    }
    this.header("Link", `<${resource}>; rel=preload; as=${as}`);
  }

  static headers() {
    const out = {};
    for (const [k, v] of this.#headers.entries()) out[k] = v;
    return out;
  }

  static send() {
    return {
      status: this.#status,
      headers: this.headers(),
      body: this.#body,
    };
  }
}

registerClass("Response", Response);
module.exports = Response;