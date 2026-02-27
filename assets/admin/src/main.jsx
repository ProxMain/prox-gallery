import { createRoot } from "react-dom/client";
import { App } from "@/app";
import "@/index.css";

const mountNode = document.getElementById("prox-gallery-admin-root");

if (mountNode) {
  createRoot(mountNode).render(<App />);
}
