import { CssBaseline, createTheme, ThemeProvider } from "@mui/material";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import "@fontsource/roboto/300.css";
import "@fontsource/roboto/400.css";
import "@fontsource/roboto/500.css";
import "@fontsource/roboto/700.css";
import "./index.css";
import App from "./App.tsx";

const theme = createTheme({
	palette: {
		mode: "light",
		primary: { main: "#2563eb" },
		secondary: { main: "#7c3aed" },
		background: { default: "#f4f6fb", paper: "#ffffff" },
		divider: "rgba(15, 23, 42, 0.08)",
	},
	shape: { borderRadius: 12 },
	typography: {
		fontFamily: '"Roboto", "Helvetica", "Arial", sans-serif',
		h5: { letterSpacing: "-0.02em" },
		subtitle1: { letterSpacing: "-0.01em" },
	},
	components: {
		MuiButton: {
			styleOverrides: {
				root: { textTransform: "none", fontWeight: 600 },
			},
		},
	},
});

const rootEl = document.getElementById("root");
if (!rootEl) {
	throw new Error('Missing root element with id "root".');
}

createRoot(rootEl).render(
	<StrictMode>
		<BrowserRouter>
			<ThemeProvider theme={theme}>
				<CssBaseline />
				<App />
			</ThemeProvider>
		</BrowserRouter>
	</StrictMode>,
);
