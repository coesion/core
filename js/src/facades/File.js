const fs = require("node:fs");
const path = require("node:path");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class File extends ModuleSupport {
  static #mounts = new Map();
  static #memory = new Map();

  static mount(name, driver = "native", options = {}) {
    this.#mounts.set(name, { driver, options });
  }

  static mounts() {
    return Array.from(this.#mounts.keys());
  }

  static #normalize(filePath) {
    const [scheme, rest] = String(filePath).includes("://") ? String(filePath).split("://") : [null, String(filePath)];
    const clean = path.posix.normalize(`/${rest}`).replace(/^\//, "");
    return { scheme, file: clean };
  }

  static #resolve(filePath) {
    const parsed = this.#normalize(filePath);
    if (parsed.scheme) return parsed;

    for (const [name, mount] of this.#mounts.entries()) {
      if (mount.driver === "native") {
        const full = path.join(mount.options.root || process.cwd(), parsed.file);
        if (fs.existsSync(full)) return { scheme: name, file: parsed.file };
      }
      if (mount.driver === "memory") {
        const key = `${name}://${parsed.file}`;
        if (this.#memory.has(key)) return { scheme: name, file: parsed.file };
      }
    }
    return { scheme: null, file: parsed.file };
  }

  static write(filePath, content) {
    const { scheme, file } = this.#resolve(filePath);
    const target = scheme ? `${scheme}://${file}` : filePath;
    const parsed = this.#normalize(target);
    const mount = parsed.scheme ? this.#mounts.get(parsed.scheme) : null;

    if (mount && mount.driver === "memory") {
      this.#memory.set(`${parsed.scheme}://${parsed.file}`, String(content));
      return;
    }

    const root = mount && mount.driver === "native" ? mount.options.root : process.cwd();
    const full = path.join(root, parsed.file);
    fs.mkdirSync(path.dirname(full), { recursive: true });
    fs.writeFileSync(full, String(content), "utf8");
  }

  static append(filePath, content) {
    const current = this.exists(filePath) ? this.read(filePath) : "";
    this.write(filePath, `${current}${content}`);
  }

  static read(filePath) {
    const { scheme, file } = this.#resolve(filePath);
    const parsed = this.#normalize(scheme ? `${scheme}://${file}` : filePath);
    const mount = parsed.scheme ? this.#mounts.get(parsed.scheme) : null;

    if (mount && mount.driver === "memory") {
      return this.#memory.get(`${parsed.scheme}://${parsed.file}`) || "";
    }

    const root = mount && mount.driver === "native" ? mount.options.root : process.cwd();
    const full = path.join(root, parsed.file);
    return fs.readFileSync(full, "utf8");
  }

  static exists(filePath) {
    const { scheme, file } = this.#resolve(filePath);
    if (!scheme) return false;
    const mount = this.#mounts.get(scheme);
    if (mount.driver === "memory") return this.#memory.has(`${scheme}://${file}`);
    return fs.existsSync(path.join(mount.options.root || process.cwd(), file));
  }

  static search(pattern = "*") {
    const isTxt = pattern.endsWith(".txt");
    const results = [];
    for (const [name, mount] of this.#mounts.entries()) {
      if (mount.driver === "memory") {
        for (const key of this.#memory.keys()) {
          if (!key.startsWith(`${name}://`)) continue;
          if (!isTxt || key.endsWith(".txt")) results.push(key);
        }
      } else if (mount.driver === "native") {
        const root = mount.options.root || process.cwd();
        for (const entry of fs.readdirSync(root)) {
          if (!isTxt || entry.endsWith(".txt")) results.push(`${name}://${entry}`);
        }
      }
    }
    return results;
  }
}

registerClass("File", File);
module.exports = File;