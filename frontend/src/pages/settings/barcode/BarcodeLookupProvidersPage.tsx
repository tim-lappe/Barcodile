import AddIcon from "@mui/icons-material/Add";
import ArrowDownwardIcon from "@mui/icons-material/ArrowDownward";
import ArrowUpwardIcon from "@mui/icons-material/ArrowUpward";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
import EditOutlinedIcon from "@mui/icons-material/EditOutlined";
import {
	Alert,
	Box,
	Button,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	FormControlLabel,
	IconButton,
	Paper,
	Switch,
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
import {
	deleteBarcodeLookupProvider,
	fetchBarcodeLookupProviders,
	patchBarcodeLookupProvider,
	postBarcodeLookupProvider,
} from "../../../api/barcodileClient";
import type { BarcodeLookupProviderDto } from "../../../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 960,
	mx: "auto",
} as const;

function kindLabel(kind: string): string {
	if (kind === "barcode_lookup_com_v3") {
		return "BarcodeLookup.com (v3)";
	}
	return kind;
}

export function BarcodeLookupProvidersPage() {
	const [rows, setRows] = useState<BarcodeLookupProviderDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<BarcodeLookupProviderDto | null>(
		null,
	);
	const [deleting, setDeleting] = useState(false);
	const [addOpen, setAddOpen] = useState(false);
	const [editTarget, setEditTarget] = useState<BarcodeLookupProviderDto | null>(
		null,
	);
	const [formLabel, setFormLabel] = useState("");
	const [formApiKey, setFormApiKey] = useState("");
	const [formEnabled, setFormEnabled] = useState(true);
	const [formSortOrder, setFormSortOrder] = useState("0");
	const [formBusy, setFormBusy] = useState(false);
	const [formError, setFormError] = useState<string | null>(null);

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchBarcodeLookupProviders();
			setRows(
				[...t].sort((a, b) => a.sortOrder - b.sortOrder || a.id.localeCompare(b.id)),
			);
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	function openAdd() {
		setFormLabel("");
		setFormApiKey("");
		setFormEnabled(true);
		setFormSortOrder(String(rows.length === 0 ? 0 : rows[rows.length - 1].sortOrder + 1));
		setFormError(null);
		setAddOpen(true);
	}

	function openEdit(row: BarcodeLookupProviderDto) {
		setEditTarget(row);
		setFormLabel(row.label);
		setFormApiKey("");
		setFormEnabled(row.enabled);
		setFormSortOrder(String(row.sortOrder));
		setFormError(null);
	}

	async function submitAdd() {
		setFormError(null);
		const label = formLabel.trim();
		const key = formApiKey.trim();
		if (label === "") {
			setFormError("Label is required.");
			return;
		}
		if (key === "") {
			setFormError("API key is required.");
			return;
		}
		const so = Number.parseInt(formSortOrder.trim(), 10);
		if (Number.isNaN(so)) {
			setFormError("Sort order must be a whole number.");
			return;
		}
		setFormBusy(true);
		try {
			await postBarcodeLookupProvider({
				label,
				apiKey: key,
				kind: "barcode_lookup_com_v3",
				enabled: formEnabled,
				sortOrder: so,
			});
			setAddOpen(false);
			await load();
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setFormBusy(false);
		}
	}

	async function submitEdit() {
		if (!editTarget) {
			return;
		}
		setFormError(null);
		const label = formLabel.trim();
		if (label === "") {
			setFormError("Label is required.");
			return;
		}
		const so = Number.parseInt(formSortOrder.trim(), 10);
		if (Number.isNaN(so)) {
			setFormError("Sort order must be a whole number.");
			return;
		}
		const patch: Record<string, unknown> = {
			label,
			enabled: formEnabled,
			sortOrder: so,
		};
		const key = formApiKey.trim();
		if (key !== "") {
			patch.apiKey = key;
		}
		setFormBusy(true);
		try {
			await patchBarcodeLookupProvider(editTarget.id, patch);
			setEditTarget(null);
			await load();
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setFormBusy(false);
		}
	}

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteBarcodeLookupProvider(deleteTarget.id);
			setDeleteTarget(null);
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Delete failed");
			setDeleteTarget(null);
		} finally {
			setDeleting(false);
		}
	}

	async function moveRow(
		sortedRows: BarcodeLookupProviderDto[],
		index: number,
		direction: -1 | 1,
	) {
		const j = index + direction;
		if (j < 0 || j >= sortedRows.length) {
			return;
		}
		const a = sortedRows[index];
		const b = sortedRows[j];
		const orderA = a.sortOrder;
		const orderB = b.sortOrder;
		setListError(null);
		try {
			await patchBarcodeLookupProvider(a.id, { sortOrder: orderB });
			await patchBarcodeLookupProvider(b.id, { sortOrder: orderA });
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Reorder failed");
		}
	}

	const sorted = [...rows].sort(
		(a, b) => a.sortOrder - b.sortOrder || a.id.localeCompare(b.id),
	);

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
				<Box>
					<Typography variant="h5" sx={{ fontWeight: 700 }}>
						Barcode lookup
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
						Providers are tried in sort order until one returns a product match
						when you create a catalog item from a barcode.
					</Typography>
				</Box>
				<Button variant="contained" startIcon={<AddIcon />} onClick={openAdd}>
					Add provider
				</Button>
			</Box>

			{listError ? (
				<Alert severity="error" sx={{ mb: 2 }} onClose={() => setListError(null)}>
					{listError}
				</Alert>
			) : null}

			{loading ? (
				<Typography color="text.secondary">Loading…</Typography>
			) : (
				<TableContainer>
					<Table size="small">
						<TableHead>
							<TableRow>
								<TableCell width={100}>Order</TableCell>
								<TableCell>Label</TableCell>
								<TableCell>Kind</TableCell>
								<TableCell width={100}>Enabled</TableCell>
								<TableCell width={120}>API key</TableCell>
								<TableCell width={160} align="right">
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{sorted.length === 0 ? (
								<TableRow>
									<TableCell colSpan={6}>
										<Typography variant="body2" color="text.secondary">
											No providers yet. Add one to enable barcode lookup when
											creating catalog items.
										</Typography>
									</TableCell>
								</TableRow>
							) : (
								sorted.map((row, index) => (
									<TableRow key={row.id}>
										<TableCell>{row.sortOrder}</TableCell>
										<TableCell>{row.label}</TableCell>
										<TableCell>{kindLabel(row.kind)}</TableCell>
										<TableCell>{row.enabled ? "Yes" : "No"}</TableCell>
										<TableCell>
											{row.apiKeyStored ? "Stored" : "—"}
										</TableCell>
										<TableCell align="right">
											<IconButton
												size="small"
												aria-label="Move up"
												disabled={index === 0}
												onClick={() => void moveRow(sorted, index, -1)}
											>
												<ArrowUpwardIcon fontSize="small" />
											</IconButton>
											<IconButton
												size="small"
												aria-label="Move down"
												disabled={index === sorted.length - 1}
												onClick={() => void moveRow(sorted, index, 1)}
											>
												<ArrowDownwardIcon fontSize="small" />
											</IconButton>
											<IconButton
												size="small"
												aria-label="Edit"
												onClick={() => openEdit(row)}
											>
												<EditOutlinedIcon fontSize="small" />
											</IconButton>
											<IconButton
												size="small"
												aria-label="Delete"
												onClick={() => setDeleteTarget(row)}
											>
												<DeleteOutlinedIcon fontSize="small" />
											</IconButton>
										</TableCell>
									</TableRow>
								))
							)}
						</TableBody>
					</Table>
				</TableContainer>
			)}

			<Dialog open={addOpen} onClose={() => !formBusy && setAddOpen(false)} fullWidth maxWidth="sm">
				<DialogTitle>Add barcode lookup provider</DialogTitle>
				<DialogContent>
					{formError ? (
						<Alert severity="error" sx={{ mb: 2 }}>
							{formError}
						</Alert>
					) : null}
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Currently only BarcodeLookup.com API v3 is supported. Use an API key
						from your BarcodeLookup.com account.
					</Typography>
					<TextField
						label="Label"
						value={formLabel}
						onChange={(e) => setFormLabel(e.target.value)}
						fullWidth
						required
						sx={{ mb: 2 }}
						helperText="Shown in the admin UI to identify this provider row."
					/>
					<TextField
						label="API key"
						value={formApiKey}
						onChange={(e) => setFormApiKey(e.target.value)}
						fullWidth
						required
						type="password"
						autoComplete="new-password"
						sx={{ mb: 2 }}
					/>
					<TextField
						label="Sort order"
						value={formSortOrder}
						onChange={(e) => setFormSortOrder(e.target.value)}
						fullWidth
						sx={{ mb: 2 }}
						slotProps={{ htmlInput: { inputMode: "numeric" } }}
						helperText="Lower numbers are tried first when resolving a barcode."
					/>
					<FormControlLabel
						control={
							<Switch
								checked={formEnabled}
								onChange={(e) => setFormEnabled(e.target.checked)}
							/>
						}
						label="Enabled"
					/>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setAddOpen(false)} disabled={formBusy}>
						Cancel
					</Button>
					<Button variant="contained" onClick={() => void submitAdd()} disabled={formBusy}>
						Save
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog
				open={editTarget !== null}
				onClose={() => !formBusy && setEditTarget(null)}
				fullWidth
				maxWidth="sm"
			>
				<DialogTitle>Edit barcode lookup provider</DialogTitle>
				<DialogContent>
					{formError ? (
						<Alert severity="error" sx={{ mb: 2 }}>
							{formError}
						</Alert>
					) : null}
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Kind: {editTarget ? kindLabel(editTarget.kind) : ""}
					</Typography>
					<TextField
						label="Label"
						value={formLabel}
						onChange={(e) => setFormLabel(e.target.value)}
						fullWidth
						required
						sx={{ mb: 2 }}
					/>
					<TextField
						label="New API key (optional)"
						value={formApiKey}
						onChange={(e) => setFormApiKey(e.target.value)}
						fullWidth
						type="password"
						autoComplete="new-password"
						sx={{ mb: 2 }}
						helperText="Leave blank to keep the existing key."
					/>
					<TextField
						label="Sort order"
						value={formSortOrder}
						onChange={(e) => setFormSortOrder(e.target.value)}
						fullWidth
						sx={{ mb: 2 }}
						slotProps={{ htmlInput: { inputMode: "numeric" } }}
					/>
					<FormControlLabel
						control={
							<Switch
								checked={formEnabled}
								onChange={(e) => setFormEnabled(e.target.checked)}
							/>
						}
						label="Enabled"
					/>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setEditTarget(null)} disabled={formBusy}>
						Cancel
					</Button>
					<Button variant="contained" onClick={() => void submitEdit()} disabled={formBusy}>
						Save
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog open={deleteTarget !== null} onClose={() => !deleting && setDeleteTarget(null)}>
				<DialogTitle>Delete provider?</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						Remove{" "}
						<strong>{deleteTarget?.label ?? ""}</strong>? This cannot be undone.
					</Typography>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setDeleteTarget(null)} disabled={deleting}>
						Cancel
					</Button>
					<Button
						color="error"
						variant="contained"
						onClick={() => void confirmDelete()}
						disabled={deleting}
					>
						Delete
					</Button>
				</DialogActions>
			</Dialog>
		</Paper>
	);
}
