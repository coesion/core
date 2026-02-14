const test = require("node:test");
const assert = require("node:assert/strict");
const { Auth, Token, CSRF, Gate, RateLimiter, SecurityHeaders, Response, Options, Session, Request } = require("../src/index");

test("Auth session resolver", () => {
  Auth.flush();
  Session.flush();
  Options.reset();
  Options.set("core.auth.session.enabled", true);
  Options.set("core.auth.bearer.enabled", true);
  Options.set("core.auth.session.key", "auth.user");

  Auth.resolver((identity, source) => ({ id: identity, source }));
  Session.set("auth.user", 7);
  const user = Auth.user();
  assert.equal(user.id, 7);
  assert.equal(user.source, "session");
});

test("Auth bearer jwt resolver", () => {
  Auth.flush();
  Session.flush();
  Options.reset();
  const token = Token.encode({ sub: 5, exp: Math.floor(Date.now() / 1000) + 60 }, "secret");
  Request.set({ headers: { authorization: `Bearer ${token}` } });
  Options.set("core.auth.jwt.secret", "secret");

  Auth.resolver((identity, source) => (source === "bearer" ? { id: identity.sub } : null));
  const user = Auth.user();
  assert.equal(user.id, 5);
});

test("CSRF token verify", () => {
  Session.flush();
  Options.reset();
  const token = CSRF.token();
  Request.set({ headers: { "x-csrf-token": token } });
  assert.equal(CSRF.verify(), true);
});

test("Gate allows", () => {
  Auth.flush();
  Session.flush();
  Options.reset();
  Gate.flush();

  Gate.define("admin", (user) => user && user.role === "admin");
  Auth.resolver(() => ({ role: "admin" }));
  Session.set("auth.user", 1);
  assert.equal(Gate.allows("admin"), true);
});

test("RateLimiter blocks after limit", () => {
  RateLimiter.flush();
  const key = `test-${Date.now()}`;
  const first = RateLimiter.check(key, 2, 60);
  const second = RateLimiter.check(key, 2, 60);
  const third = RateLimiter.check(key, 2, 60);
  assert.equal(first.allowed, true);
  assert.equal(second.allowed, true);
  assert.equal(third.allowed, false);
});

test("SecurityHeaders apply", () => {
  Response.clean();
  SecurityHeaders.apply();
  const headers = Response.headers();
  assert.equal(!!headers["X-Frame-Options"], true);
  assert.equal(!!headers["X-Content-Type-Options"], true);
});