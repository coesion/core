class MemoryCacheAdapter {
  constructor() {
    this.store = new Map();
  }

  get(key) {
    const row = this.store.get(key);
    if (!row) return undefined;
    if (row.expiresAt && row.expiresAt <= Date.now()) {
      this.store.delete(key);
      return undefined;
    }
    return row.value;
  }

  set(key, value, ttl = 0) {
    const expiresAt = ttl > 0 ? Date.now() + ttl * 1000 : 0;
    this.store.set(key, { value, expiresAt });
    return value;
  }

  exists(key) {
    return this.get(key) !== undefined;
  }

  delete(key) {
    this.store.delete(key);
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

  flush() {
    this.store.clear();
  }
}

module.exports = MemoryCacheAdapter;