import { resolve } from "node:path";
import { defineConfig } from "vitest/config";

export default defineConfig({
  resolve: { alias: { "@": resolve(import.meta.dirname, "assets/admin/src") } },
  esbuild: { jsx: "automatic" },
  test: {
    environment: "jsdom",
    include: ["tests/ui/**/*.test.{js,jsx,ts}"],
    setupFiles: ["tests/ui/setup.js"],
  },
});
