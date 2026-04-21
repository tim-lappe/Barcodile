import AddIcon from "@mui/icons-material/Add";
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
	IconButton,
	Paper,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink } from "react-router-dom";
import {
	deleteInventoryItem,
	fetchCatalogItems,
	fetchInventoryItems,
} from "../api/barcodileClient";
import type { CatalogItemDto, InventoryItemDto } from "../domain/barcodile";
import { firstBarcodeCode } from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

export function StockPage() {
	const [types, setTypes] = useState<CatalogItemDto[]>([]);
	const [rows, setRows] = useState<InventoryItemDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<InventoryItemDto | null>(
		null,
	);
	const [deleting, setDeleting] = useState(false);

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const [t, inv] = await Promise.all([
				fetchCatalogItems(),
				fetchInventoryItems(),
			]);
			setTypes(t);
			setRows(inv);
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	function barcodeLabel(typeId: string): string {
		const t = types.find((x) => x.id === typeId);
		return t ? firstBarcodeCode(t) : "";
	}

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteInventoryItem(deleteTarget.id);
			setDeleteTarget(null);
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Delete failed");
			setDeleteTarget(null);
		} finally {
			setDeleting(false);
		}
	}

	const canCreate = types.length > 0;

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
					Inventory
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					component={RouterLink}
					to="/inventory/new"
					disabled={!canCreate}
				>
					Add item
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Physical stock lines: quantity, where they live, and optional
				best-before dates. Use the editor for detailed entry.
			</Typography>
			{!canCreate && !loading && (
				<Alert severity="info" sx={{ mb: 2 }}>
					Create at least one catalog item before adding inventory.
				</Alert>
			)}
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
								<TableCell sx={{ fontWeight: 700 }}>Type</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Barcode</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Location</TableCell>
								<TableCell sx={{ fontWeight: 700 }} align="right">
									Quantity
								</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell>{row.catalogItem.name}</TableCell>
									<TableCell
										sx={{
											color: "text.secondary",
											fontFamily: "ui-monospace, monospace",
										}}
									>
										{barcodeLabel(row.catalogItem.id) || "—"}
									</TableCell>
									<TableCell>{row.location?.name ?? "—"}</TableCell>
									<TableCell align="right">{row.quantity}</TableCell>
									<TableCell align="right">
										<IconButton
											aria-label={`Edit ${row.catalogItem.name}`}
											size="small"
											component={RouterLink}
											to={`/inventory/${row.id}/edit`}
										>
											<EditOutlinedIcon fontSize="small" />
										</IconButton>
										<IconButton
											aria-label={`Delete ${row.catalogItem.name}`}
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
									<TableCell colSpan={6}>
										<Typography variant="body2" color="text.secondary">
											No inventory rows yet.
										</Typography>
									</TableCell>
								</TableRow>
							)}
						</TableBody>
					</Table>
				</TableContainer>
			)}

			<Dialog
				open={Boolean(deleteTarget)}
				onClose={() => !deleting && setDeleteTarget(null)}
				slotProps={{
					paper: { sx: { borderRadius: 2 } },
				}}
			>
				<DialogTitle>Delete inventory item</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						Remove this row for{" "}
						<strong>{deleteTarget?.catalogItem.name}</strong> (quantity{" "}
						{deleteTarget?.quantity})?
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
