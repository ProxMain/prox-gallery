import { afterEach } from "vitest";
import { cleanup } from "@testing-library/react";

HTMLDialogElement.prototype.showModal = function () {
  this.open = true;
};
HTMLDialogElement.prototype.close = function () {
  this.open = false;
};
afterEach(cleanup);
