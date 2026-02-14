const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const MemoryAdapter = require("../adapters/MemoryAdapter");
const SQLiteAdapter = require("../adapters/SQLiteAdapter");

class SQL extends ModuleSupport {
  static #adapter = new MemoryAdapter();

  static connect(adapterOrDsn = null) {
    if (adapterOrDsn && typeof adapterOrDsn === "object") {
      this.#adapter = adapterOrDsn;
      return this.#adapter;
    }

    if (typeof adapterOrDsn === "string" && adapterOrDsn.startsWith("sqlite:")) {
      const file = adapterOrDsn.replace(/^sqlite:/, "") || ":memory:";
      this.#adapter = new SQLiteAdapter(file);
      return this.#adapter;
    }

    this.#adapter = new MemoryAdapter();
    return this.#adapter;
  }

  static adapter() { return this.#adapter; }
  static query(sql, params = []) { return this.#adapter.query(sql, params); }
  static each(sql, params = []) { return this.#adapter.each(sql, params); }
  static exec(sql) { return this.#adapter.exec ? this.#adapter.exec(sql) : this.#adapter.query(sql); }
}

registerClass("SQL", SQL);
module.exports = SQL;