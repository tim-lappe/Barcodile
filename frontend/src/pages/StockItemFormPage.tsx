import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import {
	Alert,
	Box,
	Breadcrumbs,
	Button,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	FormControl,
	IconButton,
	InputLabel,
	Link,
	MenuItem,
	Paper,
	Select,
	type SelectChangeEvent,
	TextField,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useNavigate, useParams } from "react-router-dom";
import {
	catalogHasEntries,
	createInventoryItem,
	createLocation,
	fetchInventoryItem,
	fetchLocations,
	updateInventoryItem,
} from "../api/barcodileClient";
import { CatalogItemSearchInput } from "../components/CatalogItemSearchInput";
import type {
	CatalogItemId,
	InventoryItemDto,
	InventoryItemId,
	LocationId,
} from "../domain/barcodile";

const shellSx = {
	maxWidth: 920,
	mx: "auto",
} as const;

const sectionPaperSx = {
	p: { xs: 2, sm: 2.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
} as const;

type FormState = {
	catalogItemId: CatalogItemId;
	locationId: LocationId | "";
	expirationDate: string;
	publicCode: string | null;
};

function emptyForm(defaultTypeId: CatalogItemId): FormState {
	return {
		catalogItemId: defaultTypeId,
		locationId: "",
		expirationDate: "",
		publicCode: null,
	};
}

function dtoToForm(row: InventoryItemDto): FormState {
	const exp =
		row.expirationDate != null && row.expirationDate !== ""
			? row.expirationDate.slice(0, 10)
			: "";
	return {
		catalogItemId: row.catalogItem.id,
		locationId: row.location?.id ?? "",
		expirationDate: exp,
		publicCode: row.publicCode,
	};
}

function isoDatetimeFromDateInput(s: string): string | null {
	const t = s.trim();
	if (t === "") {
		return null;
	}
	const d = new Date(`${t}T12:00:00.000Z`);
	if (Number.isNaN(d.getTime())) {
		return null;
	}
	return d.toISOString();
}

export function StockItemFormPage() {
	const { id: idParam } = useParams<{ id: string }>();
	const navigate = useNavigate();
	const isEdit = Boolean(idParam);

	const [hasCatalogItems, setHasCatalogItems] = useState(false);
	const [locations, setLocations] = useState<{ id: string; name: string }[]>(
		[],
	);
	const [form, setForm] = useState<FormState>(emptyForm(""));
	const [loadError, setLoadError] = useState<string | null>(null);
	const [loading, setLoading] = useState(true);
	const [formError, setFormError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [locationDialogOpen, setLocationDialogOpen] = useState(false);
	const [newLocationName, setNewLocationName] = useState("");
	const [locationSaving, setLocationSaving] = useState(false);

	const load = useCallback(async () => {
		setLoadError(null);
		setLoading(true);
		try {
			const [hasTypes, loc] = await Promise.all([
				catalogHasEntries(),
				fetchLocations(),
			]);
			setHasCatalogItems(hasTypes);
			setLocations(loc);
			if (isEdit && idParam) {
				const row = await fetchInventoryItem(idParam as InventoryItemId);
				setForm(dtoToForm(row));
			} else {
				setForm(emptyForm(""));
			}
		} catch (e) {
			setLoadError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, [isEdit, idParam]);

	useEffect(() => {
		void load();
	}, [load]);

	async function submitForm() {
		setFormError(null);
		const catalogItemId = form.catalogItemId;
		if (!catalogItemId) {
			setFormError("Select a catalog item.");
			return;
		}
		const locationId = form.locationId === "" ? null : form.locationId;
		const expirationRaw = form.expirationDate.trim();
		const expirationDate =
			expirationRaw === "" ? null : isoDatetimeFromDateInput(expirationRaw);
		setSaving(true);
		try {
			const payload = {
				catalogItemId,
				locationId,
				expirationDate,
			};
			if (isEdit && idParam) {
				await updateInventoryItem(idParam as InventoryItemId, payload);
			} else {
				await createInventoryItem(payload);
			}
			navigate("/inventory");
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	async function addLocationQuick() {
		const name = newLocationName.trim();
		if (!name) {
			return;
		}
		setLocationSaving(true);
		try {
			const loc = await createLocation({ name });
			setLocations((prev) =>
				[...prev, loc].sort((a, b) => a.name.localeCompare(b.name)),
			);
			setForm((f) => ({ ...f, locationId: loc.id }));
			setNewLocationName("");
			setLocationDialogOpen(false);
		} catch (e) {
			setFormError(
				e instanceof Error ? e.message : "Could not create location",
			);
		} finally {
			setLocationSaving(false);
		}
	}

	const canCreate = hasCatalogItems;

	if (loading) {
		return (
			<Box sx={shellSx}>
				<Typography color="text.secondary">Loading…</Typography>
			</Box>
		);
	}

	if (loadError) {
		return (
			<Box sx={shellSx}>
				<Alert severity="error" sx={{ mb: 2 }}>
					{loadError}
				</Alert>
				<Button
					component={RouterLink}
					to="/inventory"
					startIcon={<ArrowBackIcon />}
				>
					Back to inventory
				</Button>
			</Box>
		);
	}

	if (!isEdit && !canCreate) {
		return (
			<Box sx={shellSx}>
				<Alert severity="info" sx={{ mb: 2 }}>
					Create at least one catalog item before adding inventory.
				</Alert>
				<Button
					component={RouterLink}
					to="/catalog-items/new"
					variant="contained"
				>
					New catalog item
				</Button>
			</Box>
		);
	}

	return (
		<Box sx={shellSx}>
			<Breadcrumbs sx={{ mb: 2 }} aria-label="Breadcrumb">
				<Link
					component={RouterLink}
					to="/inventory"
					underline="hover"
					color="inherit"
					variant="body2"
				>
					Inventory
				</Link>
				<Typography color="text.primary" variant="body2">
					{isEdit ? "Edit item" : "New item"}
				</Typography>
			</Breadcrumbs>

			<Box sx={{ display: "flex", alignItems: "flex-start", gap: 1.5, mb: 2 }}>
				<IconButton
					component={RouterLink}
					to="/inventory"
					aria-label="Back to inventory"
					sx={{ mt: 0.25 }}
				>
					<ArrowBackIcon />
				</IconButton>
				<Box sx={{ minWidth: 0 }}>
					<Typography variant="h5" sx={{ fontWeight: 700 }}>
						{isEdit ? "Edit inventory unit" : "New inventory unit"}
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
						One form submission adds a single physical item. The server assigns
						a numeric label code for barcodes or QR. Catalog item and location
						describe what and where this unit is.
					</Typography>
				</Box>
			</Box>

			{formError && (
				<Alert
					severity="error"
					sx={{ mb: 2 }}
					onClose={() => setFormError(null)}
				>
					{formError}
				</Alert>
			)}

			<Box sx={{ display: "flex", flexDirection: "column", gap: 2.5, mb: 10 }}>
				{isEdit && form.publicCode != null ? (
					<Paper elevation={0} sx={sectionPaperSx}>
						<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
							Label code
						</Typography>
						<Typography
							variant="body2"
							color="text.secondary"
							sx={{ mb: 1.5 }}
						>
							Fixed when this unit was created; use for printing.
						</Typography>
						<Typography
							variant="h6"
							sx={{ fontFamily: "ui-monospace, monospace", fontWeight: 600 }}
						>
							{form.publicCode}
						</Typography>
					</Paper>
				) : null}
				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 2 }}>
						Catalog item
					</Typography>
					<CatalogItemSearchInput
						label="Catalog item"
						required
						value={form.catalogItemId}
						onChange={(id) => setForm((f) => ({ ...f, catalogItemId: id }))}
					/>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Location
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Where this physical unit is stored.
					</Typography>
					<Box
						sx={{
							display: "flex",
							gap: 1,
							alignItems: "flex-start",
							flexWrap: "wrap",
							mb: 2,
						}}
					>
						<FormControl sx={{ flex: 1, minWidth: 200 }} fullWidth>
							<InputLabel id="inv-loc-label">Location</InputLabel>
							<Select<string>
								labelId="inv-loc-label"
								label="Location"
								value={form.locationId}
								onChange={(e: SelectChangeEvent) =>
									setForm((f) => ({ ...f, locationId: e.target.value }))
								}
							>
								<MenuItem value="">
									<em>None</em>
								</MenuItem>
								{locations.map((loc) => (
									<MenuItem key={loc.id} value={loc.id}>
										{loc.name}
									</MenuItem>
								))}
							</Select>
						</FormControl>
						<Button
							sx={{ mt: 0.5 }}
							variant="outlined"
							onClick={() => setLocationDialogOpen(true)}
						>
							New location
						</Button>
					</Box>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Lifecycle
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Best-before tracking when applicable.
					</Typography>
					<TextField
						label="Expiration date"
						type="date"
						value={form.expirationDate}
						onChange={(e) =>
							setForm((f) => ({ ...f, expirationDate: e.target.value }))
						}
						fullWidth
						slotProps={{ inputLabel: { shrink: true } }}
					/>
				</Paper>
			</Box>

			<Paper
				elevation={8}
				sx={{
					position: "fixed",
					left: 0,
					right: 0,
					bottom: 0,
					zIndex: 10,
					px: { xs: 2, sm: 3 },
					py: 2,
					borderRadius: 0,
					borderTop: "1px solid",
					borderColor: "divider",
				}}
			>
				<Box
					sx={{
						maxWidth: 920,
						mx: "auto",
						display: "flex",
						flexWrap: "wrap",
						gap: 1.5,
						justifyContent: "flex-end",
					}}
				>
					<Button component={RouterLink} to="/inventory" disabled={saving}>
						Cancel
					</Button>
					<Button
						variant="contained"
						onClick={() => void submitForm()}
						disabled={saving}
					>
						{saving ? "Saving…" : isEdit ? "Save changes" : "Create unit"}
					</Button>
				</Box>
			</Paper>

			<Dialog
				open={locationDialogOpen}
				onClose={() => !locationSaving && setLocationDialogOpen(false)}
				slotProps={{ paper: { sx: { borderRadius: 2 } } }}
			>
				<DialogTitle>New location</DialogTitle>
				<DialogContent>
					<TextField
						autoFocus
						margin="dense"
						label="Name"
						fullWidth
						value={newLocationName}
						onChange={(e) => setNewLocationName(e.target.value)}
					/>
				</DialogContent>
				<DialogActions sx={{ px: 3, pb: 2 }}>
					<Button
						onClick={() => setLocationDialogOpen(false)}
						disabled={locationSaving}
					>
						Cancel
					</Button>
					<Button
						variant="contained"
						onClick={() => void addLocationQuick()}
						disabled={locationSaving || !newLocationName.trim()}
					>
						{locationSaving ? "Saving…" : "Create"}
					</Button>
				</DialogActions>
			</Dialog>
		</Box>
	);
}
