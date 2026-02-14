const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class RouteGroup extends ModuleSupport {
  constructor() {
    super();
    this.routes = new Set();
    this.beforeMiddleware = [];
    this.afterMiddleware = [];
    this.pushes = [];
  }

  add(route) {
    this.routes.add(route);
    for (const middleware of this.beforeMiddleware) route.before(middleware);
    for (const middleware of this.afterMiddleware) route.after(middleware);
    for (const [resource, as] of this.pushes) route.push(resource, as);
    return this;
  }

  remove(route) {
    this.routes.delete(route);
    return this;
  }

  has(route) {
    return this.routes.has(route);
  }

  before(callback) {
    this.beforeMiddleware.push(callback);
    for (const route of this.routes) route.before(callback);
    return this;
  }

  after(callback) {
    this.afterMiddleware.push(callback);
    for (const route of this.routes) route.after(callback);
    return this;
  }

  push(resource, as = "text") {
    this.pushes.push([resource, as]);
    for (const route of this.routes) route.push(resource, as);
    return this;
  }
}

registerClass("RouteGroup", RouteGroup);
module.exports = RouteGroup;