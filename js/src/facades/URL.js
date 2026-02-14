const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function encodePart(key, value) {
  if (value && typeof value === "object" && !Array.isArray(value)) {
    return Object.entries(value).flatMap(([k, v]) => encodePart(`${key}[${k}]`, v));
  }
  if (Array.isArray(value)) {
    return value.flatMap((v) => encodePart(`${key}[]`, v));
  }
  return [`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`];
}

function parseQuery(qs) {
  const out = {};
  const input = String(qs || "").replace(/^\?/, "");
  if (!input) return out;
  for (const part of input.split("&")) {
    if (!part) continue;
    const [k, v = ""] = part.split("=");
    const key = decodeURIComponent(k);
    const val = decodeURIComponent(v);
    if (key.endsWith("[]")) {
      const base = key.slice(0, -2);
      if (!Array.isArray(out[base])) out[base] = [];
      out[base].push(val);
    } else {
      out[key] = val;
    }
  }
  return out;
}

class URL extends ModuleSupport {
  constructor(input = "") {
    super();
    this.scheme = "";
    this.user = "";
    this.pass = "";
    this.host = "";
    this.port = "";
    this.path = "";
    this.query = {};
    this.fragment = "";

    if (input) this.#parse(input);
  }

  #parse(input) {
    const raw = String(input);
    let parsed;
    try {
      parsed = new globalThis.URL(raw);
    } catch {
      parsed = new globalThis.URL(raw, "http://placeholder.local");
      if (!/^[A-Za-z][A-Za-z0-9+.-]*:/.test(raw)) {
        this.path = raw;
        return;
      }
    }
    this.scheme = parsed.protocol.replace(/:$/, "");
    this.user = parsed.username;
    this.pass = parsed.password;
    this.host = parsed.hostname;
    this.port = parsed.port ? Number(parsed.port) : "";
    this.path = parsed.pathname;
    this.query = parseQuery(parsed.search);
    this.fragment = parsed.hash.replace(/^#/, "");
  }

  toString() {
    const auth = this.user ? `${this.user}${this.pass ? `:${this.pass}` : ""}@` : "";
    const scheme = this.scheme ? `${this.scheme}://` : "";
    const host = this.host || "";
    const port = this.port ? `:${this.port}` : "";
    const path = this.path ? (String(this.path).startsWith("/") ? this.path : `/${this.path}`) : "";
    const queryParts = Object.entries(this.query).flatMap(([k, v]) => encodePart(k, v));
    const query = queryParts.length ? `?${queryParts.join("&")}` : "";
    const frag = this.fragment ? `#${this.fragment}` : "";
    return `${scheme}${auth}${host}${port}${path}${query}${frag}` || "/";
  }

  valueOf() {
    return this.toString();
  }
}

registerClass("URL", URL);
module.exports = URL;