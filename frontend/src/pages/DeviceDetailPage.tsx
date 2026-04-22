import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import {
	Alert,
	Box,
	Button,
	CircularProgress,
	Divider,
	FormControlLabel,
	List,
	ListItem,
	ListItemText,
	Paper,
	Switch,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useParams } from "react-router-dom";
import {
	fetchScannerDevice,
	patchScannerDeviceAutomations,
} from "../api/barcodileClient";
import type { ScannerDeviceDto } from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

export function DeviceDetailPage() {
	const { id } = useParams<{ id: string }>();
	const [device, setDevice] = useState<ScannerDeviceDto | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);

	const load = useCallback(async () => {
		if (!id) {
			setError("Missing device id.");
			setLoading(false);
			return;
		}
		setError(null);
		setLoading(true);
		try {
			const d = await fetchScannerDevice(id);
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

	async function persistAutomations(next: {
		automationAddInventoryOnEanScan: boolean;
		automationCreateCatalogItemIfMissingForEan: boolean;
		automationRemoveInventoryOnPublicCodeScan: boolean;
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
		});
	}

	const latestScans =
		device?.lastScannedCodes.slice().reverse().slice(0, 50) ?? [];

	return (
		<Paper elevation={0} sx={paperSx}>
			<Button
				component={RouterLink}
				to="/devices"
				startIcon={<ArrowBackIcon />}
				sx={{ mb: 2 }}
			>
				Devices
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
								Saving…
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
						<Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
							{error}
						</Alert>
					)}
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
						Automations
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						When this device scans a code, optional actions run after the scan is
						stored. Inventory public codes are numeric labels printed for each
						stock row.
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
									disabled={
										saving || !device.automationAddInventoryOnEanScan
									}
								/>
							}
							label="Create catalog item if none exists for that EAN"
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
					<Divider sx={{ my: 3 }} />
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
						Latest scans
					</Typography>
					{latestScans.length === 0 ? (
						<Typography variant="body2" color="text.secondary">
							No scans recorded yet for this device.
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
							{latestScans.map((code, i) => (
								<ListItem key={`${code}-${i}`} disablePadding>
									<ListItemText
										slotProps={{
											primary: {
												sx: {
													fontFamily: "ui-monospace, monospace",
													fontSize: 14,
												},
											},
										}}
										primary={code}
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
