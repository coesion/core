class MemoryAdapter {
  constructor() {
    this.tablesMap = new Map();
    this.pk = new Map();
  }

  createTable(name, columns, primaryKey = "id") {
    this.tablesMap.set(name, []);
    this.pk.set(name, { primaryKey, columns: [...columns] });
  }

  tables() {
    return Array.from(this.tablesMap.keys());
  }

  describe(table) {
    const meta = this.pk.get(table);
    if (!meta) return [];
    return meta.columns.map((name) => ({
      name,
      type: "text",
      primary: name === meta.primaryKey,
      default: null,
    }));
  }

  findByPk(table, value) {
    const meta = this.pk.get(table);
    if (!meta) return null;
    return this.tablesMap.get(table).find((row) => row[meta.primaryKey] === value) || null;
  }

  all(table, page = 1, limit = -1) {
    const rows = [...(this.tablesMap.get(table) || [])];
    if (limit < 0) return rows;
    const start = (Math.max(page, 1) - 1) * limit;
    return rows.slice(start, start + limit);
  }

  findWhere(table, where = null, params = []) {
    const rows = [...(this.tablesMap.get(table) || [])];
    if (!where) return rows;
    const simpleEq = String(where).match(/^\s*([A-Za-z0-9_]+)\s*=\s*\?\s*$/);
    const simpleLike = String(where).match(/^\s*([A-Za-z0-9_]+)\s+LIKE\s+\?\s*$/i);
    if (simpleEq) {
      const [, field] = simpleEq;
      return rows.filter((r) => r[field] === params[0]);
    }
    if (simpleLike) {
      const [, field] = simpleLike;
      const rx = String(params[0] || "").replace(/%/g, ".*");
      const re = new RegExp(`^${rx}$`, "i");
      return rows.filter((r) => re.test(String(r[field] || "")));
    }
    return rows;
  }

  count(table, where = null, params = []) {
    return this.findWhere(table, where, params).length;
  }

  insert(table, row) {
    const meta = this.pk.get(table);
    if (!meta) throw new Error(`Unknown table '${table}'`);
    const data = { ...row };
    if (data[meta.primaryKey] === undefined || data[meta.primaryKey] === null) {
      const max = this.tablesMap.get(table).reduce((acc, r) => Math.max(acc, Number(r[meta.primaryKey] || 0)), 0);
      data[meta.primaryKey] = max + 1;
    }
    this.tablesMap.get(table).push(data);
    return { ...data };
  }

  update(table, row) {
    const meta = this.pk.get(table);
    if (!meta) throw new Error(`Unknown table '${table}'`);
    const pk = row[meta.primaryKey];
    const rows = this.tablesMap.get(table);
    const idx = rows.findIndex((r) => r[meta.primaryKey] === pk);
    if (idx < 0) throw new Error(`Row not found for ${table}.${meta.primaryKey}=${pk}`);
    rows[idx] = { ...rows[idx], ...row };
    return { ...rows[idx] };
  }
}

module.exports = MemoryAdapter;