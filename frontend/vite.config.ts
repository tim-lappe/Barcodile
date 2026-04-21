import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

const devProxyTarget = process.env.DEV_PROXY_TARGET ?? "http://127.0.0.1:8000";

const devProxy = {
	"/api": {
		target: devProxyTarget,
		changeOrigin: true,
	},
	"/bundles": {
		target: devProxyTarget,
		changeOrigin: true,
	},
	"/_profiler": {
		target: devProxyTarget,
		changeOrigin: false,
	},
	"/_wdt": {
		target: devProxyTarget,
		changeOrigin: false,
	},
} as const;

export default defineConfig({
	plugins: [react()],
	server: {
		proxy: devProxy,
	},
	preview: {
		proxy: devProxy,
	},
});
