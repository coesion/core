const fs = require("node:fs");
const path = require("node:path");

class FilesCacheAdapter {
  constructor(options = {}) {
    this.cacheDir = options.cache_dir || path.join(process.cwd(), ".core-cache");
    fs.mkdirSync(this.cacheDir, { recursive: true });
  }

  #file(key) {
    const safe = Buffer.from(String(key)).toString("hex");
    return path.join(this.cacheDir, `${safe}.cache.json`);
  }

  get(key) {
    const file = this.#file(key);
    if (!fs.existsSync(file)) return undefined;
    const row = JSON.parse(fs.readFileSync(file, "utf8"));
    if (row.expiresAt && row.expiresAt <= Date.now()) {
      fs.rmSync(file, { force: true });
      return undefined;
    }
    return row.value;
  }

  set(key, value, ttl = 0) {
    const file = this.#file(key);
    const expiresAt = ttl > 0 ? Date.now() + ttl * 1000 : 0;
    fs.writeFileSync(file, JSON.stringify({ value, expiresAt }), "utf8");
    return value;
  }

  exists(key) {
    return this.get(key) !== undefined;
  }

  delete(key) {
    fs.rmSync(this.#file(key), { force: true });
  }

  inc(key, by = 1) {
    const now = Number(this.get(key) || 0) + by;
    this.set(key, now);
    return now;
  }

  dec(key, by = 1) {
    const now = Number(this.get(key) || 0) - by;
    this.set(key, now);
    return now;
  }
}

module.exports = FilesCacheAdapter;