import AddIcon from "@mui/icons-material/Add";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
import {
	Alert,
	Box,
	Button,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	FormControl,
	IconButton,
	InputLabel,
	MenuItem,
	Paper,
	Select,
	type SelectChangeEvent,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	TextField,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink } from "react-router-dom";
import {
	deletePrinterDevice,
	fetchPrinterDevices,
	fetchPrinterDiscoveryOptions,
	fetchPrinterDrivers,
	postPrinterDevice,
} from "../api/barcodileClient";
import type {
	DiscoveredPrinterOptionDto,
	PrinterDeviceDto,
	PrinterDriverDto,
} from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

function connectionSummary(conn: Record<string, unknown>): string {
	const id = conn.printerIdentifier;
	if (typeof id === "string" && id.length > 0) {
		return id;
	}
	return JSON.stringify(conn);
}

export function PrintersPage() {
	const [rows, setRows] = useState<PrinterDeviceDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<PrinterDeviceDto | null>(
		null,
	);
	const [deleting, setDeleting] = useState(false);
	const [addOpen, setAddOpen] = useState(false);
	const [drivers, setDrivers] = useState<PrinterDriverDto[]>([]);
	const [formDriver, setFormDriver] = useState("");
	const [options, setOptions] = useState<DiscoveredPrinterOptionDto[]>([]);
	const [optionsLoading, setOptionsLoading] = useState(false);
	const [addError, setAddError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [formDeviceId, setFormDeviceId] = useState("");
	const [formConnection, setFormConnection] = useState<Record<
		string,
		unknown
	> | null>(null);
	const [formName, setFormName] = useState("");

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchPrinterDevices();
			setRows(t.sort((a, b) => a.name.localeCompare(b.name)));
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	const loadDiscovery = useCallback(async (driverCode: string) => {
		setOptionsLoading(true);
		setOptions([]);
		setFormDeviceId("");
		setFormConnection(null);
		try {
			const o = await fetchPrinterDiscoveryOptions(driverCode);
			setOptions(o);
			if (o.length > 0) {
				const first = o[0];
				if (first) {
					setFormDeviceId(first.deviceIdentifier);
					setFormConnection({ ...first.suggestedConnection });
				}
			}
		} catch (e) {
			setAddError(e instanceof Error ? e.message : "Request failed");
			setOptions([]);
		} finally {
			setOptionsLoading(false);
		}
	}, []);

	const openAdd = useCallback(async () => {
		setAddError(null);
		setFormName("");
		setFormDriver("");
		setFormDeviceId("");
		setFormConnection(null);
		setOptions([]);
		setAddOpen(true);
		setOptionsLoading(true);
		try {
			const d = await fetchPrinterDrivers();
			setDrivers(d);
			if (d.length > 0) {
				const code = d[0]?.code ?? "";
				setFormDriver(code);
				await loadDiscovery(code);
			} else {
				setOptionsLoading(false);
			}
		} catch (e) {
			setAddError(e instanceof Error ? e.message : "Request failed");
			setDrivers([]);
			setOptionsLoading(false);
		}
	}, [loadDiscovery]);

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deletePrinterDevice(deleteTarget.id);
			setDeleteTarget(null);
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Delete failed");
			setDeleteTarget(null);
		} finally {
			setDeleting(false);
		}
	}

	async function submitAdd() {
		const name = formName.trim();
		if (!formDriver || !formConnection || !name) {
			setAddError("Choose a driver, discovered printer, and enter a name.");
			return;
		}
		setAddError(null);
		setSaving(true);
		try {
			await postPrinterDevice({
				driverCode: formDriver,
				connection: formConnection,
				name,
			});
			setAddOpen(false);
			await load();
		} catch (e) {
			setAddError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	function onDriverChange(e: SelectChangeEvent<string>) {
		const code = e.target.value;
		setFormDriver(code);
		setAddError(null);
		void loadDiscovery(code);
	}

	function onDeviceChange(e: SelectChangeEvent<string>) {
		const id = e.target.value;
		setFormDeviceId(id);
		const opt = options.find((o) => o.deviceIdentifier === id);
		if (opt) {
			setFormConnection({ ...opt.suggestedConnection });
		}
	}

	const canSave =
		drivers.length > 0 &&
		options.length > 0 &&
		formConnection !== null &&
		Object.keys(formConnection).length > 0;

	return (
		<Paper elevation={0} sx={paperSx}>
			<Box
				sx={{
					display: "flex",
					flexWrap: "wrap",
					alignItems: "center",
					justifyContent: "space-between",
					gap: 2,
					mb: 2,
				}}
			>
				<Typography variant="h5" sx={{ fontWeight: 700 }}>
					Printers
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					onClick={() => void openAdd()}
				>
					Add printer
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Register label printers by driver. Brother QL devices use the{" "}
				<code>brother_ql</code> stack (USB or Linux kernel backend). The API runs
				Python helpers inside the same container; USB access may require a
				privileged container or host device passthrough.
			</Typography>
			{listError && (
				<Alert
					severity="error"
					sx={{ mb: 2 }}
					onClose={() => setListError(null)}
				>
					{listError}
				</Alert>
			)}
			{loading ? (
				<Typography color="text.secondary">Loading…</Typography>
			) : (
				<TableContainer
					sx={{
						border: "1px solid",
						borderColor: "divider",
						borderRadius: 1,
					}}
				>
					<Table size="small">
						<TableHead>
							<TableRow>
								<TableCell sx={{ fontWeight: 700 }}>Name</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Driver</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Connection</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell>
										<RouterLink
											to={`/printers/${row.id}`}
											style={{
												color: "inherit",
												fontWeight: 600,
												textDecoration: "none",
											}}
										>
											{row.name}
										</RouterLink>
									</TableCell>
									<TableCell sx={{ color: "text.secondary", fontSize: 13 }}>
										{row.driverCode}
									</TableCell>
									<TableCell
										sx={{
											color: "text.secondary",
											fontFamily: "ui-monospace, monospace",
											fontSize: 13,
											wordBreak: "break-all",
										}}
									>
										{connectionSummary(row.connection)}
									</TableCell>
									<TableCell align="right">
										<IconButton
											aria-label={`Delete ${row.name}`}
											size="small"
											color="error"
											onClick={() => setDeleteTarget(row)}
										>
											<DeleteOutlinedIcon fontSize="small" />
										</IconButton>
									</TableCell>
								</TableRow>
							))}
							{rows.length === 0 && (
								<TableRow>
									<TableCell colSpan={4}>
										<Typography variant="body2" color="text.secondary">
											No printers yet.
										</Typography>
									</TableCell>
								</TableRow>
							)}
						</TableBody>
					</Table>
				</TableContainer>
			)}

			<Dialog
				open={addOpen}
				onClose={() => !saving && setAddOpen(false)}
				slotProps={{
					paper: { sx: { borderRadius: 2 } },
				}}
				fullWidth
				maxWidth="sm"
			>
				<DialogTitle>Add printer</DialogTitle>
				<DialogContent>
					{addError && (
						<Alert severity="error" sx={{ mb: 2 }}>
							{addError}
						</Alert>
					)}
					{drivers.length === 0 && !optionsLoading && (
						<Alert severity="info" sx={{ mb: 2 }}>
							No printer drivers are registered on the server.
						</Alert>
					)}
					{drivers.length > 0 && (
						<FormControl fullWidth sx={{ mb: 2, mt: 0.5 }}>
							<InputLabel id="driver-select-label">Driver</InputLabel>
							<Select
								labelId="driver-select-label"
								label="Driver"
								value={formDriver}
								onChange={onDriverChange}
							>
								{drivers.map((d) => (
									<MenuItem key={d.code} value={d.code}>
										{d.label}
									</MenuItem>
								))}
							</Select>
						</FormControl>
					)}
					{optionsLoading ? (
						<Typography color="text.secondary">Discovering printers…</Typography>
					) : (
						<>
							{formDriver && options.length === 0 && (
								<Alert severity="info" sx={{ mb: 2 }}>
									No printers found for this driver. Connect a Brother QL via
									USB, ensure PyUSB can see it, or try the Linux kernel backend
									when <code>/dev/usb/lp0</code> is available.
								</Alert>
							)}
							<FormControl fullWidth sx={{ mb: 2 }}>
								<InputLabel id="device-select-label">Discovered printer</InputLabel>
								<Select
									labelId="device-select-label"
									label="Discovered printer"
									value={formDeviceId}
									onChange={onDeviceChange}
									disabled={options.length === 0}
								>
									{options.map((o) => (
										<MenuItem key={o.deviceIdentifier} value={o.deviceIdentifier}>
											{o.label}
										</MenuItem>
									))}
								</Select>
							</FormControl>
							<TextField
								label="Name"
								fullWidth
								value={formName}
								onChange={(e) => setFormName(e.target.value)}
								margin="normal"
								sx={{ mt: 0 }}
							/>
						</>
					)}
				</DialogContent>
				<DialogActions sx={{ px: 3, pb: 2 }}>
					<Button onClick={() => setAddOpen(false)} disabled={saving}>
						Cancel
					</Button>
					<Button
						variant="contained"
						onClick={() => void submitAdd()}
						disabled={saving || optionsLoading || !canSave}
					>
						{saving ? "Saving…" : "Save"}
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog
				open={Boolean(deleteTarget)}
				onClose={() => !deleting && setDeleteTarget(null)}
				slotProps={{
					paper: { sx: { borderRadius: 2 } },
				}}
			>
				<DialogTitle>Delete printer</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						Remove <strong>{deleteTarget?.name}</strong> from the list?
					</Typography>
				</DialogContent>
				<DialogActions sx={{ px: 3, pb: 2 }}>
					<Button onClick={() => setDeleteTarget(null)} disabled={deleting}>
						Cancel
					</Button>
					<Button
						color="error"
						variant="contained"
						onClick={() => void confirmDelete()}
						disabled={deleting}
					>
						{deleting ? "Deleting…" : "Delete"}
					</Button>
				</DialogActions>
			</Dialog>
		</Paper>
	);
}
