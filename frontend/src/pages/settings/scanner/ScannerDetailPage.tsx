import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import {
	Alert,
	Box,
	Button,
	CircularProgress,
	Divider,
	FormControl,
	FormControlLabel,
	InputLabel,
	List,
	ListItem,
	ListItemText,
	MenuItem,
	Paper,
	Select,
	Switch,
	TextField,
	Typography,
} from "@mui/material";
import type { SelectChangeEvent } from "@mui/material/Select";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useParams } from "react-router-dom";
import {
	fetchPrinterDevices,
	fetchScannerDevice,
	patchScannerDeviceAutomations,
	postScannerDeviceSimulateInput,
} from "../../../api/barcodileClient";
import type {
	PrinterDeviceDto,
	PrinterDeviceId,
	ScannerDeviceDto,
} from "../../../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

type ScanRow = {
	code: string;
	key: string;
};

function latestScanRows(codes: string[]): ScanRow[] {
	const seen = new Map<string, number>();
	return codes
		.slice()
		.reverse()
		.slice(0, 50)
		.map((code) => {
			const occurrence = (seen.get(code) ?? 0) + 1;
			seen.set(code, occurrence);
			return { code, key: `${code}-${occurrence}` };
		});
}

export function ScannerDetailPage() {
	const { id } = useParams<{ id: string }>();
	const [device, setDevice] = useState<ScannerDeviceDto | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [printers, setPrinters] = useState<PrinterDeviceDto[]>([]);
	const [simulateText, setSimulateText] = useState("");
	const [simulating, setSimulating] = useState(false);

	const load = useCallback(async () => {
		if (!id) {
			setError("Missing scanner id.");
			setLoading(false);
			return;
		}
		setError(null);
		setLoading(true);
		try {
			const [d, printerRows] = await Promise.all([
				fetchScannerDevice(id),
				fetchPrinterDevices(),
			]);
			setDevice(d);
			setPrinters(printerRows);
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

	async function persistAutomations(next: {
		automationAddInventoryOnBarcodeScan: boolean;
		automationCreateCatalogItemIfMissingForBarcode: boolean;
		automationRemoveInventoryOnPublicCodeScan: boolean;
		automationPrintInventoryLabelOnBarcodeScan: boolean;
		automationPrinterDeviceId: PrinterDeviceId | null;
	}) {
		if (!id) {
			return;
		}
		setSaving(true);
		setError(null);
		try {
			const d = await patchScannerDeviceAutomations(id, next);
			setDevice(d);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	function setAddInventoryOnBarcodeScan(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnBarcodeScan: checked,
			automationCreateCatalogItemIfMissingForBarcode: checked
				? device.automationCreateCatalogItemIfMissingForBarcode
				: false,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintInventoryLabelOnBarcodeScan: checked
				? device.automationPrintInventoryLabelOnBarcodeScan
				: false,
			automationPrinterDeviceId: checked
				? device.automationPrinterDeviceId
				: null,
		});
	}

	function setCreateCatalogIfMissing(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnBarcodeScan:
				device.automationAddInventoryOnBarcodeScan,
			automationCreateCatalogItemIfMissingForBarcode: checked,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintInventoryLabelOnBarcodeScan:
				device.automationPrintInventoryLabelOnBarcodeScan,
			automationPrinterDeviceId: device.automationPrinterDeviceId,
		});
	}

	function setRemoveOnPublicCode(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnBarcodeScan:
				device.automationAddInventoryOnBarcodeScan,
			automationCreateCatalogItemIfMissingForBarcode:
				device.automationCreateCatalogItemIfMissingForBarcode,
			automationRemoveInventoryOnPublicCodeScan: checked,
			automationPrintInventoryLabelOnBarcodeScan:
				device.automationPrintInventoryLabelOnBarcodeScan,
			automationPrinterDeviceId: device.automationPrinterDeviceId,
		});
	}

	function setPrintLabelOnBarcodeScan(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnBarcodeScan:
				device.automationAddInventoryOnBarcodeScan,
			automationCreateCatalogItemIfMissingForBarcode:
				device.automationCreateCatalogItemIfMissingForBarcode,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintInventoryLabelOnBarcodeScan: checked,
			automationPrinterDeviceId: checked
				? (device.automationPrinterDeviceId ?? printers[0]?.id ?? null)
				: null,
		});
	}

	function setAutomationPrinter(event: SelectChangeEvent) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnBarcodeScan:
				device.automationAddInventoryOnBarcodeScan,
			automationCreateCatalogItemIfMissingForBarcode:
				device.automationCreateCatalogItemIfMissingForBarcode,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintInventoryLabelOnBarcodeScan:
				device.automationPrintInventoryLabelOnBarcodeScan,
			automationPrinterDeviceId: event.target.value || null,
		});
	}

	async function submitSimulatedScan() {
		if (!id) {
			return;
		}
		const text = simulateText;
		if (!text) {
			return;
		}
		setSimulating(true);
		setError(null);
		try {
			const d = await postScannerDeviceSimulateInput(id, { text });
			setDevice(d);
			setSimulateText("");
		} catch (e) {
			setError(e instanceof Error ? e.message : "Simulate scan failed");
		} finally {
			setSimulating(false);
		}
	}

	if (loading) {
		return (
			<Paper elevation={0} sx={paperSx}>
				<Box sx={{ display: "flex", alignItems: "center", gap: 2, py: 2 }}>
					<CircularProgress size={22} />
					<Typography color="text.secondary">Loading scanner…</Typography>
				</Box>
			</Paper>
		);
	}

	if (!device) {
		return (
			<Paper elevation={0} sx={paperSx}>
				{error && (
					<Alert severity="error" sx={{ mb: 2 }}>
						{error}
					</Alert>
				)}
				<Button component={RouterLink} to="/settings/scanners" startIcon={<ArrowBackIcon />}>
					Back to scanners
				</Button>
			</Paper>
		);
	}

	return (
		<Paper elevation={0} sx={paperSx}>
			<Box sx={{ display: "flex", flexWrap: "wrap", alignItems: "center", gap: 2, mb: 2 }}>
				<Button component={RouterLink} to="/settings/scanners" startIcon={<ArrowBackIcon />} size="small">
					Scanners
				</Button>
				<Typography variant="h5" sx={{ fontWeight: 700, flex: 1 }}>
					{device.name}
				</Typography>
				{saving && (
					<Typography variant="body2" color="text.secondary">
						Saving...
					</Typography>
				)}
			</Box>
			<Typography
				variant="body2"
				color="text.secondary"
				sx={{
					fontFamily: "ui-monospace, monospace",
					wordBreak: "break-all",
					mb: 2,
				}}
			>
				{device.deviceIdentifier}
			</Typography>
			{error && (
				<Alert
					severity="error"
					sx={{ mb: 2 }}
					onClose={() => setError(null)}
				>
					{error}
				</Alert>
			)}
			<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
				Automations
			</Typography>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				When this scanner reads a code, optional actions run after the scan
				is stored. Inventory public codes are numeric labels printed for
				each stock row.
			</Typography>
			<FormControlLabel
				control={
					<Switch
						checked={device.automationAddInventoryOnBarcodeScan}
						onChange={(_, c) => setAddInventoryOnBarcodeScan(c)}
						disabled={saving}
					/>
				}
				label="Add inventory item when a catalog barcode is scanned"
			/>
			<Box sx={{ pl: { xs: 0, sm: 4 }, mt: 1, mb: 2 }}>
				<FormControlLabel
					control={
						<Switch
							checked={device.automationCreateCatalogItemIfMissingForBarcode}
							onChange={(_, c) => setCreateCatalogIfMissing(c)}
							disabled={saving || !device.automationAddInventoryOnBarcodeScan}
						/>
					}
					label="Create catalog item if none exists for that barcode"
				/>
			</Box>
			<FormControlLabel
				control={
					<Switch
						checked={device.automationRemoveInventoryOnPublicCodeScan}
						onChange={(_, c) => setRemoveOnPublicCode(c)}
						disabled={saving}
					/>
				}
				label="Remove inventory item when its Barcodile public code is scanned"
			/>
			<Box sx={{ mt: 2 }}>
				<FormControlLabel
					control={
						<Switch
							checked={device.automationPrintInventoryLabelOnBarcodeScan}
							onChange={(_, c) => setPrintLabelOnBarcodeScan(c)}
							disabled={saving || !device.automationAddInventoryOnBarcodeScan}
						/>
					}
					label="Print item label when a catalog barcode is scanned"
				/>
			</Box>
			<Box sx={{ pl: { xs: 0, sm: 4 }, mt: 1, mb: 2, maxWidth: 420 }}>
				<FormControl fullWidth size="small">
					<InputLabel id="scanner-automation-printer-label">
						Printer
					</InputLabel>
					<Select
						labelId="scanner-automation-printer-label"
						label="Printer"
						value={device.automationPrinterDeviceId ?? ""}
						onChange={setAutomationPrinter}
						disabled={
							saving ||
							!device.automationAddInventoryOnBarcodeScan ||
							!device.automationPrintInventoryLabelOnBarcodeScan
						}
					>
						<MenuItem value="">
							<em>No printer selected</em>
						</MenuItem>
						{printers.map((printer) => (
							<MenuItem key={printer.id} value={printer.id}>
								{printer.name}
							</MenuItem>
						))}
					</Select>
				</FormControl>
				{printers.length === 0 && (
					<Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
						Add a printer before enabling automatic label printing.
					</Typography>
				)}
			</Box>
			<Divider sx={{ my: 3 }} />
			<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
				Simulate scan
			</Typography>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Record a scan for this scanner (same as{" "}
				<code>{`bin/console scanner:simulate -d ${device.deviceIdentifier} …`}</code>
				).
			</Typography>
			<Box
				sx={{
					display: "flex",
					flexWrap: "wrap",
					gap: 1.5,
					alignItems: "flex-start",
				}}
			>
				<TextField
					label="Scanned text"
					value={simulateText}
					onChange={(e) => setSimulateText(e.target.value)}
					size="small"
					sx={{ flex: "1 1 220px", minWidth: 0 }}
					slotProps={{
						htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
					}}
				/>
				<Button
					variant="contained"
					onClick={() => void submitSimulatedScan()}
					disabled={simulating || simulateText.trim() === ""}
				>
					{simulating ? "Sending…" : "Simulate"}
				</Button>
			</Box>
			<Divider sx={{ my: 3 }} />
			<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
				Recent scans
			</Typography>
			{device.lastScannedCodes.length === 0 ? (
				<Typography variant="body2" color="text.secondary">
					No scans recorded yet.
				</Typography>
			) : (
				<List dense disablePadding>
					{latestScanRows(device.lastScannedCodes).map((row) => (
						<ListItem key={row.key} disableGutters sx={{ py: 0.25 }}>
							<ListItemText
								primary={row.code}
								slotProps={{
									primary: {
										sx: {
											fontFamily: "ui-monospace, monospace",
											fontSize: 13,
										},
									},
								}}
							/>
						</ListItem>
					))}
				</List>
			)}
		</Paper>
	);
}
