import { resolve } from "node:path";
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
  plugins: [tailwindcss()],
  esbuild: {
    jsx: "automatic"
  },
  resolve: {
    alias: {
      "@": resolve(__dirname, "assets/admin/src")
    }
  },
  build: {
    manifest: true,
    emptyOutDir: true,
    outDir: "assets/admin/build",
    rollupOptions: {
      input: resolve(__dirname, "assets/admin/src/main.jsx"),
      output: {
        entryFileNames: "assets/admin-[hash].js",
        chunkFileNames: "assets/chunk-[hash].js",
        assetFileNames: "assets/[name]-[hash][extname]"
      }
    }
  }
});
