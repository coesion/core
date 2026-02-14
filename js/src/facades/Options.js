const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Filter = require("./Filter");
const Event = require("./Event");

function asObject(value) {
  return value && typeof value === "object" && !Array.isArray(value);
}

function deepMerge(target, source) {
  const out = { ...target };
  for (const [key, value] of Object.entries(source)) {
    if (asObject(value) && asObject(out[key])) out[key] = deepMerge(out[key], value);
    else out[key] = value;
  }
  return out;
}

function setByPath(target, keyPath, value) {
  const parts = keyPath.split(".").filter(Boolean);
  if (!parts.length) return;
  let ref = target;
  for (let i = 0; i < parts.length - 1; i += 1) {
    const key = parts[i];
    if (!asObject(ref[key])) ref[key] = {};
    ref = ref[key];
  }
  ref[parts.at(-1)] = value;
}

function getByPath(target, keyPath, fallback = undefined) {
  const parts = keyPath.split(".").filter(Boolean);
  let ref = target;
  for (const part of parts) {
    if (!asObject(ref) && !Array.isArray(ref)) return fallback;
    if (!(part in ref)) return fallback;
    ref = ref[part];
  }
  return ref;
}

class Options extends ModuleSupport {
  static #fields = {};

  static reset() {
    this.#fields = {};
  }

  static all() {
    return structuredClone(this.#fields);
  }

  static get(pathKey, fallback = null) {
    return getByPath(this.#fields, pathKey, fallback);
  }

  static set(pathKey, value) {
    setByPath(this.#fields, pathKey, value);
    return value;
  }

  static merge(input) {
    this.#fields = deepMerge(this.#fields, input || {});
  }

  static loadArray(input, prefixPath = null) {
    const filtered = Filter.apply("load.array", Filter.apply("load", input));
    if (prefixPath) this.set(prefixPath, filtered);
    else this.merge(filtered);
    Event.trigger("options.loaded", filtered);
  }

  static loadJSON(filePath, prefixPath = null) {
    const data = JSON.parse(fs.readFileSync(filePath, "utf8"));
    const filtered = Filter.apply("load.json", Filter.apply("load", data));
    this.loadArray(filtered, prefixPath);
  }

  static loadENV(dir, envName = ".env", prefixPath = null) {
    const filePath = path.join(dir, envName);
    const data = fs.readFileSync(filePath, "utf8");
    const results = {};
    for (const raw of data.split(/\r?\n/)) {
      const line = raw.trim();
      if (!line || line.startsWith("#") || !line.includes("=")) continue;
      const idx = line.indexOf("=");
      const key = line.slice(0, idx).replace(/^export\s+/, "").trim();
      const value = line.slice(idx + 1).trim().replace(/^['"]|['"]$/g, "");
      results[key] = value.replace(/\$\{([A-Za-z0-9_]+)\}/g, (_, name) => results[name] || process.env[name] || "");
      process.env[key] = results[key];
    }
    const filtered = Filter.apply("load.env", Filter.apply("load", results));
    this.loadArray(filtered, prefixPath);
  }
}

registerClass("Options", Options);
module.exports = Options;