const test = require("node:test");
const assert = require("node:assert/strict");
const { Token } = require("../src/index");

test("Token encode known value", () => {
  const token = Token.encode("TEST", "1234");
  assert.equal(token, "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.IlRFU1Qi.zPCpn5hHX3CdtmvSDt_apcanyuDjGT9W8KcCgTMyrXE");
});

test("Token decode", () => {
  const value = Token.decode("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.IlRFU1Qi.zPCpn5hHX3CdtmvSDt_apcanyuDjGT9W8KcCgTMyrXE", "1234");
  assert.equal(value, "TEST");
});

test("Token wrong secret throws", () => {
  assert.throws(() => Token.decode("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.IlRFU1Qi.zPCpn5hHX3CdtmvSDt_apcanyuDjGT9W8KcCgTMyrXE", "41231"));
});

test("Token invalid token throws", () => {
  assert.throws(() => Token.decode("eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.IlRFU1Qi", "1234"));
});