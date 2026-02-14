class SQLiteAdapter {
  constructor(file = ":memory:") {
    this.file = file;
    this.db = null;
    this.enabled = false;

    try {
      const sqlite = require("node:sqlite");
      this.db = new sqlite.DatabaseSync(file);
      this.enabled = true;
    } catch {
      this.enabled = false;
    }
  }

  assertEnabled() {
    if (!this.enabled) throw new Error("SQLite adapter requires Node runtime with node:sqlite (Node 22+)");
  }

  query(sql, params = []) {
    this.assertEnabled();
    const stmt = this.db.prepare(sql);
    if (/^\s*(insert|update|delete|create|drop|alter)/i.test(sql)) {
      return stmt.run(...(Array.isArray(params) ? params : Object.values(params || {})));
    }
    return stmt.all(...(Array.isArray(params) ? params : Object.values(params || {})));
  }

  each(sql, params = []) { return this.query(sql, params); }
  exec(sql) { return this.query(sql); }

  tables() {
    this.assertEnabled();
    return this.db.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'").all().map((r) => r.name);
  }

  describe(table) {
    this.assertEnabled();
    return this.db.prepare(`PRAGMA table_info(${table})`).all().map((r) => ({ name: r.name, type: r.type, primary: !!r.pk, default: r.dflt_value }));
  }

  findByPk(table, value) {
    const desc = this.describe(table);
    const pk = (desc.find((x) => x.primary) || { name: "id" }).name;
    return this.db.prepare(`SELECT * FROM ${table} WHERE ${pk} = ? LIMIT 1`).get(value) || null;
  }

  all(table, page = 1, limit = -1) {
    if (limit < 0) return this.db.prepare(`SELECT * FROM ${table}`).all();
    const offset = Math.max(0, (page - 1) * limit);
    return this.db.prepare(`SELECT * FROM ${table} LIMIT ? OFFSET ?`).all(limit, offset);
  }

  findWhere(table, where = null, params = []) {
    if (!where) return this.all(table);
    return this.db.prepare(`SELECT * FROM ${table} WHERE ${where}`).all(...(Array.isArray(params) ? params : Object.values(params || {})));
  }

  count(table, where = null, params = []) {
    const row = where
      ? this.db.prepare(`SELECT COUNT(1) as c FROM ${table} WHERE ${where}`).get(...(Array.isArray(params) ? params : Object.values(params || {})))
      : this.db.prepare(`SELECT COUNT(1) as c FROM ${table}`).get();
    return Number(row.c || 0);
  }

  insert(table, row) {
    const keys = Object.keys(row);
    const placeholders = keys.map(() => "?").join(",");
    this.db.prepare(`INSERT INTO ${table} (${keys.join(",")}) VALUES (${placeholders})`).run(...keys.map((k) => row[k]));
    const id = this.db.prepare("SELECT last_insert_rowid() AS id").get().id;
    const found = this.findByPk(table, id);
    return found || { ...row, id };
  }

  update(table, row) {
    const desc = this.describe(table);
    const pk = (desc.find((x) => x.primary) || { name: "id" }).name;
    const keys = Object.keys(row).filter((k) => k !== pk);
    const set = keys.map((k) => `${k} = ?`).join(", ");
    this.db.prepare(`UPDATE ${table} SET ${set} WHERE ${pk} = ?`).run(...keys.map((k) => row[k]), row[pk]);
    return this.findByPk(table, row[pk]);
  }
}

module.exports = SQLiteAdapter;