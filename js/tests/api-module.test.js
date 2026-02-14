const test = require("node:test");
const assert = require("node:assert/strict");
const { API, REST, Resource, Route, Response, Request, Filter } = require("../src/index");

class CategoryResource extends Resource {
  static rows = [
    { id: 1, name: "Lifestyle", author: "a" },
    { id: 2, name: "News", author: "b" },
  ];

  expose(fields) {
    return { id: fields.id, name: fields.name, author: fields.author };
  }

  static async __rows() {
    return [...this.rows];
  }
}

class ArticleResource extends Resource {
  expose(fields) {
    return { id: fields.id, title: fields.title, meta: { author: fields.author } };
  }
}

async function reset() {
  Route.reset();
  Response.clean();
  Filter.reset();
  Request.set({ uri: "/", method: "get", query: {}, headers: {}, data: {} });
}

test("Resource projection", async () => {
  await reset();
  Filter.add("api.ArticleResource.getProjectionFields", () => "title");
  const resource = new ArticleResource({ id: 10, title: "Hello", author: "Alice" });
  const payload = resource.jsonSerialize();
  assert.equal(payload.title, "Hello");
  assert.equal(payload.id, 10);
  assert.equal(payload.meta, undefined);
});

test("API resource list and single", async () => {
  await reset();
  Request.set({ query: { page: 1, limit: 2 } });
  API.resource("/categories", { class: CategoryResource, sql: { table: "categories", primary_key: "id" } });

  await Route.dispatch("/categories", "get");
  const list = JSON.parse(Response.body());
  assert.equal(list.data.length, 2);
  assert.equal(list.pagination.count, 2);

  Response.clean();
  await Route.dispatch("/categories/1", "get");
  const one = JSON.parse(Response.body());
  assert.equal(one.data.name, "Lifestyle");
  assert.equal(one.data.id, 1);
});

test("REST expose maps methods", async () => {
  await reset();
  REST.expose("bucket", {
    list: () => "LIST",
    read: (id) => `READ:${id}`,
  });

  await Route.dispatch("/bucket", "get");
  assert.equal(Response.body(), "LIST");

  Response.clean();
  await Route.dispatch("/bucket/7", "get");
  assert.equal(Response.body(), "READ:7");
});