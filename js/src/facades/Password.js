const crypto = require("node:crypto");
const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class Password extends ModuleSupport {
  static make(value) {
    const salt = crypto.randomBytes(16);
    const hash = crypto.scryptSync(String(value), salt, 64);
    return `scrypt$${salt.toString("hex")}$${hash.toString("hex")}`;
  }

  static verify(value, encoded) {
    const [algo, saltHex, hashHex] = String(encoded).split("$");
    if (algo !== "scrypt" || !saltHex || !hashHex) return false;
    const hash = crypto.scryptSync(String(value), Buffer.from(saltHex, "hex"), 64);
    return crypto.timingSafeEqual(hash, Buffer.from(hashHex, "hex"));
  }

  static compare(a, b) {
    const left = Buffer.from(String(a));
    const right = Buffer.from(String(b));
    if (left.length !== right.length) return false;
    return crypto.timingSafeEqual(left, right);
  }
}

registerClass("Password", Password);
module.exports = Password;
