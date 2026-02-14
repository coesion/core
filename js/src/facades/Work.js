const { registerClass } = require("../kernel/registry");
const ModuleSupport = require("./Module");

class TaskCoroutine {
  constructor(id, iterator) {
    this.id = id;
    this.iterator = iterator;
    this.value = undefined;
    this.done = false;
    this.first = true;
  }

  pass(value) {
    this.value = value;
  }

  run() {
    if (this.done) return undefined;
    const result = this.first ? this.iterator.next() : this.iterator.next(this.value);
    this.first = false;
    this.done = !!result.done;
    return result.value;
  }

  complete() {
    return this.done;
  }
}

class Work extends ModuleSupport {
  static #queue = [];

  static add(taskFactory) {
    const iterator = taskFactory();
    this.#queue.push(new TaskCoroutine(this.#queue.length + 1, iterator));
  }

  static run() {
    while (this.#queue.some((task) => !task.complete())) {
      for (const task of this.#queue) {
        if (!task.complete()) task.run();
      }
    }
    this.#queue = [];
  }

  static flush() {
    this.#queue = [];
  }
}

registerClass("TaskCoroutine", TaskCoroutine);
registerClass("Work", Work);
module.exports = { Work, TaskCoroutine };