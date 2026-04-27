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
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useParams } from "react-router-dom";
import {
	fetchPrinterDevices,
	fetchScannerDevice,
	patchScannerDeviceAutomations,
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
	const [printers, setPrinters] = useState<PrinterDeviceDto[]>([]);
	const [device, setDevice] = useState<ScannerDeviceDto | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);

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
			setPrinters(
				printerRows.sort((a, b) => a.name.localeCompare(b.name)),
			);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Request failed");
			setDevice(null);
			setPrinters([]);
		} finally {
			setLoading(false);
		}
	}, [id]);

	useEffect(() => {
		void load();
	}, [load]);

	async function persistAutomations(next: {
		automationAddInventoryOnEanScan: boolean;
		automationCreateCatalogItemIfMissingForEan: boolean;
		automationRemoveInventoryOnPublicCodeScan: boolean;
		automationPrintLabelAfterEanAddInventory: boolean;
		automationLabelPrinterDeviceId: string | null;
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

	function setAddOnEan(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnEanScan: checked,
			automationCreateCatalogItemIfMissingForEan: checked
				? device.automationCreateCatalogItemIfMissingForEan
				: false,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintLabelAfterEanAddInventory: checked
				? device.automationPrintLabelAfterEanAddInventory
				: false,
			automationLabelPrinterDeviceId: checked
				? device.automationLabelPrinterDeviceId
				: null,
		});
	}

	function setCreateCatalogIfMissing(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnEanScan: device.automationAddInventoryOnEanScan,
			automationCreateCatalogItemIfMissingForEan: checked,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintLabelAfterEanAddInventory:
				device.automationPrintLabelAfterEanAddInventory,
			automationLabelPrinterDeviceId: device.automationLabelPrinterDeviceId,
		});
	}

	function setPrintLabelAfterEan(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnEanScan: device.automationAddInventoryOnEanScan,
			automationCreateCatalogItemIfMissingForEan:
				device.automationCreateCatalogItemIfMissingForEan,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintLabelAfterEanAddInventory: checked,
			automationLabelPrinterDeviceId: checked
				? device.automationLabelPrinterDeviceId
				: null,
		});
	}

	function setLabelPrinterId(value: PrinterDeviceId | "") {
		if (!device) {
			return;
		}
		const automationLabelPrinterDeviceId =
			value === "" ? null : value;
		void persistAutomations({
			automationAddInventoryOnEanScan: device.automationAddInventoryOnEanScan,
			automationCreateCatalogItemIfMissingForEan:
				device.automationCreateCatalogItemIfMissingForEan,
			automationRemoveInventoryOnPublicCodeScan:
				device.automationRemoveInventoryOnPublicCodeScan,
			automationPrintLabelAfterEanAddInventory:
				device.automationPrintLabelAfterEanAddInventory,
			automationLabelPrinterDeviceId,
		});
	}

	function setRemoveOnPublicCode(checked: boolean) {
		if (!device) {
			return;
		}
		void persistAutomations({
			automationAddInventoryOnEanScan: device.automationAddInventoryOnEanScan,
			automationCreateCatalogItemIfMissingForEan:
				device.automationCreateCatalogItemIfMissingForEan,
			automationRemoveInventoryOnPublicCodeScan: checked,
			automationPrintLabelAfterEanAddInventory:
				device.automationPrintLabelAfterEanAddInventory,
			automationLabelPrinterDeviceId: device.automationLabelPrinterDeviceId,
		});
	}

	const latestScans = latestScanRows(device?.lastScannedCodes ?? []);

	return (
		<Paper elevation={0} sx={paperSx}>
			<Button
				component={RouterLink}
				to="/settings/scanner"
				startIcon={<ArrowBackIcon />}
				sx={{ mb: 2 }}
			>
				Scanner
			</Button>
			{loading ? (
				<Box sx={{ display: "flex", alignItems: "center", gap: 2 }}>
					<CircularProgress size={28} />
					<Typography color="text.secondary">Loading...</Typography>
				</Box>
			) : error && !device ? (
				<Alert severity="error">{error}</Alert>
			) : device ? (
				<>
					<Box
						sx={{
							display: "flex",
							flexWrap: "wrap",
							alignItems: "baseline",
							justifyContent: "space-between",
							gap: 2,
							mb: 2,
						}}
					>
						<Typography variant="h5" sx={{ fontWeight: 700 }}>
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
								checked={device.automationAddInventoryOnEanScan}
								onChange={(_, c) => setAddOnEan(c)}
								disabled={saving}
							/>
						}
						label="Add inventory item when an EAN code is scanned"
					/>
					<Box sx={{ pl: { xs: 0, sm: 4 }, mt: 1, mb: 2 }}>
						<FormControlLabel
							control={
								<Switch
									checked={device.automationCreateCatalogItemIfMissingForEan}
									onChange={(_, c) => setCreateCatalogIfMissing(c)}
									disabled={saving || !device.automationAddInventoryOnEanScan}
								/>
							}
							label="Create catalog item if none exists for that EAN"
						/>
					</Box>
					<Box sx={{ pl: { xs: 0, sm: 4 }, mt: 0, mb: 2 }}>
						<FormControlLabel
							control={
								<Switch
									checked={device.automationPrintLabelAfterEanAddInventory}
									onChange={(_, c) => setPrintLabelAfterEan(c)}
									disabled={saving || !device.automationAddInventoryOnEanScan}
								/>
							}
							label="Print label for the new row immediately after the scan"
						/>
						<FormControl
							size="small"
							sx={{ display: "block", mt: 1.5, maxWidth: 420 }}
							disabled={
								saving ||
								!device.automationAddInventoryOnEanScan ||
								!device.automationPrintLabelAfterEanAddInventory
							}
						>
							<InputLabel id="scanner-automation-label-printer">
								Label printer
							</InputLabel>
							<Select
								labelId="scanner-automation-label-printer"
								label="Label printer"
								value={device.automationLabelPrinterDeviceId ?? ""}
								onChange={(e) =>
									setLabelPrinterId(e.target.value as PrinterDeviceId | "")
								}
							>
								<MenuItem value="">
									<em>
										{printers.length === 0
											? "No label printers"
											: "Choose a printer"}
									</em>
								</MenuItem>
								{printers.map((p) => (
									<MenuItem key={p.id} value={p.id}>
										{p.name}
									</MenuItem>
								))}
							</Select>
						</FormControl>
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
					<Divider sx={{ my: 3 }} />
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
						Latest scans
					</Typography>
					{latestScans.length === 0 ? (
						<Typography variant="body2" color="text.secondary">
							No scans recorded yet for this scanner.
						</Typography>
					) : (
						<List
							dense
							sx={{
								border: "1px solid",
								borderColor: "divider",
								borderRadius: 1,
								maxHeight: 360,
								overflow: "auto",
							}}
						>
							{latestScans.map((scan) => (
								<ListItem key={scan.key} disablePadding>
									<ListItemText
										slotProps={{
											primary: {
												sx: {
													fontFamily: "ui-monospace, monospace",
													fontSize: 14,
												},
											},
										}}
										primary={scan.code}
									/>
								</ListItem>
							))}
						</List>
					)}
				</>
			) : null}
		</Paper>
	);
}
