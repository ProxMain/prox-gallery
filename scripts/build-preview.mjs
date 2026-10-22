import { mkdir, readFile, writeFile } from "node:fs/promises";
import { dirname, resolve } from "node:path";
import { build } from "vite";
import config from "../vite.admin.config.js";

// The isolated fixture adapter and the real application become one portable HTML file.
const destination = resolve(process.argv[2] ?? "dev/prox-gallery-preview.html");
const result = await build({
  ...config,
  configFile: false,
  build: {
    ...config.build,
    write: false,
    manifest: false,
    rollupOptions: {
      input: resolve("dev/preview.jsx"),
      output: { format: "iife", inlineDynamicImports: true },
    },
  },
});
const output = (Array.isArray(result) ? result[0] : result).output;
const javascript = output
  .filter((file) => file.type === "chunk")
  .map((file) => file.code)
  .join("\n");
const css = output
  .filter((file) => file.type === "asset" && file.fileName.endsWith(".css"))
  .map((file) => file.source)
  .join("\n");
let html = await readFile("dev/index.html", "utf8");
html = html.replace(
  "</head>",
  () => `<style>${css.replaceAll("</style", "<\\/style")}</style></head>`,
);
html = html.replace(
  /<script type="module" src="\.\/preview\.jsx"><\/script>/,
  () => `<script>${javascript.replaceAll("</script", "<\\/script")}</script>`,
);
await mkdir(dirname(destination), { recursive: true });
await writeFile(destination, html);
console.log(`Portable design preview: ${destination}`);
