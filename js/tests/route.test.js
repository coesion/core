const test = require("node:test");
const assert = require("node:assert/strict");
const { Route, Response, Event, Options } = require("../src/index");

async function reset() {
  Route.reset();
  Response.clean();
  Event.reset();
  Options.reset();
}

test("Route basic routing and events", async () => {
  await reset();
  let start = false;
  let end = false;

  Route.onEvent("start", () => { start = true; });
  Route.onEvent("end", () => { end = true; });

  Route.get("/", () => "index");
  await Route.dispatch("/", "get");

  assert.equal(start, true);
  assert.equal(end, true);
  assert.equal(Response.body(), "index");
});

test("Route dynamic parameter extraction", async () => {
  await reset();

  Route.on("/post/:a/:b", (a, b) => `${b}-${a}`);
  await Route.dispatch("/post/1324/fefifo", "get");

  assert.equal(Response.body(), "fefifo-1324");
});

test("Route middleware order", async () => {
  await reset();

  Route.on("/middle", () => "-Test-")
    .before(() => "AA")
    .before(() => "B")
    .after(() => "AA")
    .after(() => "B");

  await Route.dispatch("/middle", "get");
  assert.equal(Response.body(), "BAA-Test-AAB");
});

test("Route tags and URL reverse routing", async () => {
  await reset();

  Route.on("/user/:id", (id) => `USER${id}`).tag("user");
  const u1 = String(Route.URL("user"));
  const u2 = String(Route.URL("user", { id: 42 }));

  assert.equal(u1, "/user");
  assert.equal(u2, "/user/42");
});

test("Route compile static route wins dynamic", async () => {
  await reset();

  Route.get("/user/:id", (id) => `D${id}`);
  Route.get("/user/settings", () => "S");

  Route.compile();
  await Route.dispatch("/user/settings", "get");

  assert.equal(Response.body(), "S");
});

test("Route triggers 404 event", async () => {
  await reset();
  let notFound = false;
  Event.on(404, () => { notFound = true; });

  await Route.dispatch("/missing", "get");
  assert.equal(notFound, true);
});