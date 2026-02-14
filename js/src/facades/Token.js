const crypto = require("node:crypto");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

function b64urlEncode(input) {
  return Buffer.from(input)
    .toString("base64")
    .replace(/=/g, "")
    .replace(/\+/g, "-")
    .replace(/\//g, "_");
}

function b64urlDecode(input) {
  const padded = input.replace(/-/g, "+").replace(/_/g, "/") + "===".slice((input.length + 3) % 4);
  return Buffer.from(padded, "base64").toString("utf8");
}

class Token extends ModuleSupport {
  static encode(payload, secret) {
    const header = { typ: "JWT", alg: "HS256" };
    const p1 = b64urlEncode(JSON.stringify(header));
    const p2 = b64urlEncode(JSON.stringify(payload));
    const sign = crypto.createHmac("sha256", secret).update(`${p1}.${p2}`).digest("base64")
      .replace(/=/g, "").replace(/\+/g, "-").replace(/\//g, "_");
    return `${p1}.${p2}.${sign}`;
  }

  static decode(token, secret) {
    const parts = String(token).split(".");
    if (parts.length !== 3) throw new Error("InvalidToken");
    const [p1, p2, sig] = parts;
    const expected = crypto.createHmac("sha256", secret).update(`${p1}.${p2}`).digest("base64")
      .replace(/=/g, "").replace(/\+/g, "-").replace(/\//g, "_");
    if (expected !== sig) throw new Error("WrongSecret");
    return JSON.parse(b64urlDecode(p2));
  }
}

registerClass("Token", Token);
module.exports = Token;