import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import PrintOutlinedIcon from "@mui/icons-material/PrintOutlined";
import {
	Alert,
	Box,
	Button,
	CircularProgress,
	Paper,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useParams } from "react-router-dom";
import { fetchPrinterDevice, postPrinterTestPrint } from "../api/barcodileClient";
import type { PrinterDeviceDto } from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

export function PrinterDetailPage() {
	const { id } = useParams<{ id: string }>();
	const [device, setDevice] = useState<PrinterDeviceDto | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);
	const [printing, setPrinting] = useState(false);
	const [printOk, setPrintOk] = useState<string | null>(null);

	const load = useCallback(async () => {
		if (!id) {
			setError("Missing printer id.");
			setLoading(false);
			return;
		}
		setError(null);
		setLoading(true);
		try {
			const d = await fetchPrinterDevice(id);
			setDevice(d);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Request failed");
			setDevice(null);
		} finally {
			setLoading(false);
		}
	}, [id]);

	useEffect(() => {
		void load();
	}, [load]);

	async function onTestPrint() {
		if (!id) {
			return;
		}
		setPrintOk(null);
		setPrinting(true);
		setError(null);
		try {
			const r = await postPrinterTestPrint(id);
			setPrintOk(
				r.status === "queued" ? "Test label sent to the printer." : `Status: ${r.status}`,
			);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Print failed");
		} finally {
			setPrinting(false);
		}
	}

	return (
		<Paper elevation={0} sx={paperSx}>
			<Button
				component={RouterLink}
				to="/printers"
				startIcon={<ArrowBackIcon />}
				sx={{ mb: 2 }}
			>
				Printers
			</Button>
			{loading ? (
				<Box sx={{ display: "flex", alignItems: "center", gap: 2 }}>
					<CircularProgress size={28} />
					<Typography color="text.secondary">Loading…</Typography>
				</Box>
			) : error && !device ? (
				<Alert severity="error">{error}</Alert>
			) : device ? (
				<>
					<Typography variant="h5" sx={{ fontWeight: 700, mb: 2 }}>
						{device.name}
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
						Driver:{" "}
						<Box component="span" sx={{ fontFamily: "ui-monospace, monospace" }}>
							{device.driverCode}
						</Box>
					</Typography>
					<Typography
						component="pre"
						variant="body2"
						sx={{
							fontFamily: "ui-monospace, monospace",
							fontSize: 12,
							wordBreak: "break-all",
							whiteSpace: "pre-wrap",
							mb: 2,
							p: 1.5,
							borderRadius: 1,
							border: "1px solid",
							borderColor: "divider",
							bgcolor: "action.hover",
						}}
					>
						{JSON.stringify(device.connection, null, 2)}
					</Typography>
					{error && (
						<Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
							{error}
						</Alert>
					)}
					{printOk && (
						<Alert severity="success" sx={{ mb: 2 }} onClose={() => setPrintOk(null)}>
							{printOk}
						</Alert>
					)}
					<Button
						variant="contained"
						startIcon={<PrintOutlinedIcon />}
						onClick={() => void onTestPrint()}
						disabled={printing}
					>
						{printing ? "Printing…" : "Print test label"}
					</Button>
				</>
			) : null}
		</Paper>
	);
}
