const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class HTTP_Response {
  constructor(body = "", status = 200, headers = {}) {
    this.body = body;
    this.status = status;
    this.headers = headers;
  }

  toString() {
    return String(this.body);
  }
}

class HTTP_Request {
  constructor(method = "get", url = "", headers = {}, data = null) {
    this.method = String(method).toUpperCase();
    this.url = url;
    this.headers = headers;
    this.data = data;
  }

  toString() {
    const parsed = new globalThis.URL(String(this.url).startsWith("http") ? this.url : `http://${this.url}`);
    const start = `${this.method} ${parsed.pathname}${parsed.search} HTTP/1.1\r\n`;
    const host = `Host: ${parsed.host}\r\n`;
    const head = Object.entries(this.headers).map(([k, v]) => `${k}: ${v}\r\n`).join("");
    const body = this.data === null || this.data === undefined ? "" : (typeof this.data === "string" ? this.data : JSON.stringify(this.data));
    return `${start}${host}${head}\r\n${body}`;
  }
}

class HTTP extends ModuleSupport {
  static #headers = new Map();
  static #userAgent = "CoreJS";
  static #proxy = "";
  static #lastResponseHeader = "";

  static addHeader(name, value) { this.#headers.set(name, value); }
  static removeHeader(name) { this.#headers.delete(name); }
  static headers(name = null) {
    if (name !== null) return this.#headers.get(name) || "";
    return Object.fromEntries(this.#headers.entries());
  }

  static userAgent(value = null) { if (value !== null) this.#userAgent = value; return this.#userAgent; }
  static proxy(value = null) { if (value !== null) this.#proxy = value; return this.#proxy; }

  static setLastResponseHeader(raw) { this.#lastResponseHeader = String(raw || ""); }
  static lastResponseHeader() {
    const out = {};
    for (const line of this.#lastResponseHeader.split(/\r?\n/)) {
      const idx = line.indexOf(":");
      if (idx < 1) continue;
      const key = line.slice(0, idx).trim();
      const val = line.slice(idx + 1).trim();
      if (!out[key]) out[key] = [];
      out[key].push(val);
    }
    return out;
  }
}

registerClass("HTTP", HTTP);
registerClass("HTTP_Request", HTTP_Request);
registerClass("HTTP_Response", HTTP_Response);
module.exports = { HTTP, HTTP_Request, HTTP_Response };