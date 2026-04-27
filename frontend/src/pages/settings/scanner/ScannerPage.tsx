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
	deleteScannerDevice,
	fetchScannerDeviceInputOptions,
	fetchScannerDevices,
	postScannerDevice,
} from "../../../api/barcodileClient";
import type {
	InputDeviceOptionDto,
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

export function ScannerPage() {
	const [rows, setRows] = useState<ScannerDeviceDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<ScannerDeviceDto | null>(
		null,
	);
	const [deleting, setDeleting] = useState(false);
	const [addOpen, setAddOpen] = useState(false);
	const [options, setOptions] = useState<InputDeviceOptionDto[]>([]);
	const [optionsLoading, setOptionsLoading] = useState(false);
	const [addError, setAddError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [formDeviceId, setFormDeviceId] = useState("");
	const [formName, setFormName] = useState("");

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchScannerDevices();
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

	const openAdd = useCallback(async () => {
		setAddError(null);
		setFormDeviceId("");
		setFormName("");
		setAddOpen(true);
		setOptionsLoading(true);
		try {
			const o = await fetchScannerDeviceInputOptions();
			setOptions(o);
			if (o.length > 0) {
				setFormDeviceId(o[0]?.deviceIdentifier ?? "");
			}
		} catch (e) {
			setAddError(e instanceof Error ? e.message : "Request failed");
			setOptions([]);
		} finally {
			setOptionsLoading(false);
		}
	}, []);

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteScannerDevice(deleteTarget.id);
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
		if (!formDeviceId || !name) {
			setAddError("Choose a device and enter a name.");
			return;
		}
		setAddError(null);
		setSaving(true);
		try {
			await postScannerDevice({
				deviceIdentifier: formDeviceId,
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

	function onDeviceChange(e: SelectChangeEvent<string>) {
		setFormDeviceId(e.target.value);
	}

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
					Scanner
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					onClick={() => void openAdd()}
				>
					Add scanner
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Register barcode scanners and other input hardware. Paths come from
				Linux <code>/dev/input/by-id</code> when the API runs on a host with
				those devices.
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
				<Typography color="text.secondary">Loading...</Typography>
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
								<TableCell sx={{ fontWeight: 700 }}>
									Device identifier
								</TableCell>
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
											to={`/settings/scanner/${row.id}`}
											style={{
												color: "inherit",
												fontWeight: 600,
												textDecoration: "none",
											}}
										>
											{row.name}
										</RouterLink>
									</TableCell>
									<TableCell
										sx={{
											color: "text.secondary",
											fontFamily: "ui-monospace, monospace",
											fontSize: 13,
											wordBreak: "break-all",
										}}
									>
										{row.deviceIdentifier}
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
									<TableCell colSpan={3}>
										<Typography variant="body2" color="text.secondary">
											No scanners yet.
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
				<DialogTitle>Add scanner</DialogTitle>
				<DialogContent>
					{addError && (
						<Alert severity="error" sx={{ mb: 2 }}>
							{addError}
						</Alert>
					)}
					{optionsLoading ? (
						<Typography color="text.secondary">Loading devices...</Typography>
					) : (
						<>
							{options.length === 0 && (
								<Alert severity="info" sx={{ mb: 2 }}>
									No entries under /dev/input/by-id. Run the API on Linux with
									input hardware, or ensure the container can see /dev/input.
								</Alert>
							)}
							<FormControl fullWidth sx={{ mb: 2, mt: 0.5 }}>
								<InputLabel id="device-select-label">Device</InputLabel>
								<Select
									labelId="device-select-label"
									label="Device"
									value={formDeviceId}
									onChange={onDeviceChange}
									disabled={options.length === 0}
								>
									{options.map((o) => (
										<MenuItem
											key={o.deviceIdentifier}
											value={o.deviceIdentifier}
										>
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
						disabled={saving || optionsLoading || options.length === 0}
					>
						{saving ? "Saving..." : "Save"}
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
				<DialogTitle>Delete scanner</DialogTitle>
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
						{deleting ? "Deleting..." : "Delete"}
					</Button>
				</DialogActions>
			</Dialog>
		</Paper>
	);
}
