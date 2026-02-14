const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Filter = require("./Filter");

class Collection extends ModuleSupport {
  static wrap(resourceClass, data, page, limit, count) {
    if (!resourceClass) throw new Error("[API] Resource class is required");
    const projector = resourceClass.buildProjector();
    return {
      data: data.map((row) => new resourceClass(row, projector)),
      pagination: { page, limit, count },
    };
  }

  static fromRows(resourceClass, rows, page = 1, limit = 10) {
    const p = Number(Filter.apply(`api.${resourceClass.name}.page`, page));
    const l = Number(Filter.apply(`api.${resourceClass.name}.limit`, limit));
    const start = Math.max(0, (p - 1) * l);
    const slice = rows.slice(start, start + l);
    return this.wrap(resourceClass, slice, p, l, rows.length);
  }
}

registerClass("Collection", Collection);
module.exports = Collection;