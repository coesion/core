const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");
const Route = require("./Route");

class API extends ModuleSupport {
  static resource(path, options = {}) {
    const cfg = {
      class: null,
      list_mode: "list",
      sql: { table: null, raw: null, primary_key: "id" },
      ...options,
      sql: { table: null, raw: null, primary_key: "id", ...(options.sql || {}) },
    };

    const resourceClass = cfg.class;
    if (!resourceClass) return;
    const endpoint = (String(path).replace(/\/+$/, "") || "/");

    Route.on(endpoint, async () => {
      resourceClass.setExposure(cfg.list_mode);
      const rows = await resourceClass.__rows();
      return resourceClass.fromRows(rows);
    });

    Route.on(`${endpoint}/:id`, async (id) => {
      const rows = await resourceClass.__rows();
      const pkey = cfg.sql.primary_key || "id";
      const row = rows.find((x) => String(x[pkey]) === String(id));
      return { data: row ? new resourceClass(row) : null };
    });

    Route.on(`${endpoint}/:id/:parameter`, async (id, parameter) => {
      const rows = await resourceClass.__rows();
      const pkey = cfg.sql.primary_key || "id";
      const row = rows.find((x) => String(x[pkey]) === String(id));
      if (!row) return { data: null };
      const projector = () => (x) => ({ [parameter]: x[parameter], [resourceClass.PKEY]: x[resourceClass.PKEY] });
      return { data: new resourceClass(row, projector()) };
    });
  }
}

registerClass("API", API);
module.exports = API;