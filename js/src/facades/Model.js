const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const SQL = require("./SQL");

class Model extends ModuleSupport {
  static #tableByClass = new Map();
  static #pkByClass = new Map();

  static persistOn(table) {
    Model.#tableByClass.set(this, table);
    return this;
  }

  static table() {
    if (Model.#tableByClass.has(this)) return Model.#tableByClass.get(this);
    return `${this.name.toLowerCase()}s`;
  }

  static primaryKeyField() {
    return Model.#pkByClass.get(this) || "id";
  }

  static usePrimaryKey(field) {
    Model.#pkByClass.set(this, field);
    return this;
  }

  static schema() {
    const adapter = SQL.adapter();
    return typeof adapter.describe === "function" ? adapter.describe(this.table()) : [];
  }

  static fields() {
    return this.schema().map((x) => x.name);
  }

  static create(data = {}) {
    const adapter = SQL.adapter();
    const row = adapter.insert(this.table(), data);
    return new this(row);
  }

  static load(pk) {
    const adapter = SQL.adapter();
    const row = adapter.findByPk(this.table(), pk);
    return row ? new this(row) : null;
  }

  static where(where = null, params = []) {
    const adapter = SQL.adapter();
    return adapter.findWhere(this.table(), where, params).map((row) => new this(row));
  }

  static count(where = null, params = []) {
    const adapter = SQL.adapter();
    return adapter.count(this.table(), where, params);
  }

  static all(page = 1, limit = -1) {
    const adapter = SQL.adapter();
    return adapter.all(this.table(), page, limit).map((row) => new this(row));
  }

  constructor(data = {}) {
    super();
    Object.assign(this, data);
  }

  primaryKey() {
    return this[this.constructor.primaryKeyField()];
  }

  save() {
    const adapter = SQL.adapter();
    const row = adapter.update(this.constructor.table(), this.export());
    Object.assign(this, row);
    return this;
  }

  export(transformer = null) {
    const raw = { ...this };
    return typeof transformer === "function" ? transformer(raw) : raw;
  }
}

registerClass("Model", Model);
module.exports = Model;
