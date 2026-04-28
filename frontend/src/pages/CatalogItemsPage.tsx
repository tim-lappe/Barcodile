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
import { Link as RouterLink, useNavigate } from "react-router-dom";
import {
	catalogItemImageUrl,
	deleteCatalogItem,
	fetchCatalogItems,
} from "../api/barcodileClient";
import { catalogItemNewPathWithPicnicProduct } from "../catalog/catalogItemCreationSources";
import { AddCatalogItemStrategyDialog } from "../components/AddCatalogItemStrategyDialog";
import { CreateCatalogItemFromBarcodeDialog } from "../components/CreateCatalogItemFromBarcodeDialog";
import { PicnicProductSearchDialog } from "../components/PicnicProductSearchDialog";
import {
	type CatalogItemAttributeKey,
	type CatalogItemDto,
	type CatalogItemId,
	formatCatalogItemAttributeSummary,
	formatVolumeShort,
	formatWeightShort,
} from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

function formatAttributesPreview(row: CatalogItemDto): string {
	const links = row.catalogItemAttributes ?? [];
	if (links.length === 0) {
		return "—";
	}
	const parts = links.map((l) =>
		formatCatalogItemAttributeSummary(
			l.attribute as CatalogItemAttributeKey,
			l.value,
		),
	);
	const s = parts.join(", ");
	return s.length > 56 ? `${s.slice(0, 56)}…` : s;
}

function formatSizePreview(row: CatalogItemDto): string {
	const v = formatVolumeShort(row.volume);
	const w = formatWeightShort(row.weight);
	const parts = [v, w].filter(Boolean);
	return parts.length ? parts.join(" · ") : "—";
}

export function CatalogItemsPage() {
	const navigate = useNavigate();
	const [rows, setRows] = useState<CatalogItemDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<CatalogItemDto | null>(null);
	const [deleting, setDeleting] = useState(false);
	const [addStrategyOpen, setAddStrategyOpen] = useState(false);
	const [picnicSearchOpen, setPicnicSearchOpen] = useState(false);
	const [barcodeCreateOpen, setBarcodeCreateOpen] = useState(false);

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchCatalogItems();
			setRows(t);
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteCatalogItem(deleteTarget.id);
			setDeleteTarget(null);
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Delete failed");
			setDeleteTarget(null);
		} finally {
			setDeleting(false);
		}
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
					Catalog items
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					onClick={() => setAddStrategyOpen(true)}
				>
					Add catalog item
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Product templates: default volume and weight, structured facts such as
				alcohol by volume, and an optional barcode that identifies the catalog
				item. Open a row for the full editor.
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
								<TableCell
									sx={{ fontWeight: 700, width: 56 }}
									aria-label="Image"
								/>
								<TableCell sx={{ fontWeight: 700 }}>Name</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Volume / weight</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Barcode</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Attributes</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell sx={{ width: 56, py: 1, verticalAlign: "middle" }}>
										{row.imageFileName ? (
											<Box
												component="img"
												src={catalogItemImageUrl(row.id, row.imageFileName)}
												alt=""
												sx={{
													width: 40,
													height: 40,
													objectFit: "contain",
													borderRadius: 0.5,
													display: "block",
													bgcolor: "action.hover",
												}}
											/>
										) : (
											<Typography
												variant="caption"
												color="text.disabled"
												sx={{
													display: "block",
													width: 40,
													textAlign: "center",
												}}
											>
												—
											</Typography>
										)}
									</TableCell>
									<TableCell>{row.name}</TableCell>
									<TableCell sx={{ color: "text.secondary" }}>
										{formatSizePreview(row)}
									</TableCell>
									<TableCell
										sx={{ fontFamily: "ui-monospace, monospace", fontSize: 13 }}
									>
										{row.barcode?.code ?? "—"}
									</TableCell>
									<TableCell sx={{ maxWidth: 280, color: "text.secondary" }}>
										{formatAttributesPreview(row)}
									</TableCell>
									<TableCell align="right">
										<IconButton
											aria-label={`Edit ${row.name}`}
											size="small"
											component={RouterLink}
											to={`/catalog-items/${row.id}/edit`}
										>
											<EditOutlinedIcon fontSize="small" />
										</IconButton>
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
									<TableCell colSpan={6}>
										<Typography variant="body2" color="text.secondary">
											No catalog items yet.
										</Typography>
									</TableCell>
								</TableRow>
							)}
						</TableBody>
					</Table>
				</TableContainer>
			)}

			<AddCatalogItemStrategyDialog
				open={addStrategyOpen}
				onClose={() => setAddStrategyOpen(false)}
				onChooseSource={(id) => {
					if (id === "picnic") {
						setPicnicSearchOpen(true);
					}
				}}
				onChooseBarcodeCreate={() => setBarcodeCreateOpen(true)}
			/>
			<CreateCatalogItemFromBarcodeDialog
				open={barcodeCreateOpen}
				onClose={() => setBarcodeCreateOpen(false)}
				onCreated={(id: CatalogItemId) => {
					void load();
					navigate(`/catalog-items/${id}/edit`);
				}}
			/>
			<PicnicProductSearchDialog
				open={picnicSearchOpen}
				onClose={() => setPicnicSearchOpen(false)}
				onProductChosen={(productId) => {
					navigate(catalogItemNewPathWithPicnicProduct(productId));
				}}
			/>

			<Dialog
				open={Boolean(deleteTarget)}
				onClose={() => !deleting && setDeleteTarget(null)}
				slotProps={{
					paper: { sx: { borderRadius: 2 } },
				}}
			>
				<DialogTitle>Delete catalog item</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						Delete <strong>{deleteTarget?.name}</strong>? All inventory items
						and its barcode are removed as well.
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
