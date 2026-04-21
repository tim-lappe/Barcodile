import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import {
	Alert,
	Box,
	Breadcrumbs,
	Button,
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
import { useCallback, useEffect, useMemo, useState } from "react";
import { Link as RouterLink, useNavigate, useParams } from "react-router-dom";
import {
	createLocation,
	fetchLocation,
	fetchLocations,
	updateLocation,
} from "../api/barcodileClient";
import {
	forbiddenParentIdsForEdit,
	type LocationDto,
	type LocationId,
	parentIdOf,
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
	name: string;
	parentId: LocationId | "";
};

function emptyForm(): FormState {
	return { name: "", parentId: "" };
}

function dtoToForm(row: LocationDto): FormState {
	const pid = parentIdOf(row);
	return {
		name: row.name,
		parentId: pid ?? "",
	};
}

export function LocationFormPage() {
	const { id: idParam } = useParams<{ id: string }>();
	const navigate = useNavigate();
	const isEdit = Boolean(idParam);

	const [allLocations, setAllLocations] = useState<LocationDto[]>([]);
	const [form, setForm] = useState<FormState>(emptyForm);
	const [loading, setLoading] = useState(isEdit);
	const [loadError, setLoadError] = useState<string | null>(null);
	const [formError, setFormError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);

	const load = useCallback(async () => {
		setLoadError(null);
		setLoading(true);
		try {
			const list = await fetchLocations();
			setAllLocations(list);
			if (isEdit && idParam) {
				const row = await fetchLocation(idParam as LocationId);
				setForm(dtoToForm(row));
			} else {
				setForm(emptyForm());
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

	const parentOptions = useMemo(() => {
		const sorted = [...allLocations].sort((a, b) =>
			a.name.localeCompare(b.name),
		);
		if (!isEdit || !idParam) {
			return sorted;
		}
		const forbidden = forbiddenParentIdsForEdit(
			allLocations,
			idParam as LocationId,
		);
		return sorted.filter((l) => !forbidden.has(l.id));
	}, [allLocations, isEdit, idParam]);

	async function submit() {
		const name = form.name.trim();
		if (!name) {
			setFormError("Name is required.");
			return;
		}
		const parentId = form.parentId === "" ? null : form.parentId;
		setFormError(null);
		setSaving(true);
		try {
			if (isEdit && idParam) {
				await updateLocation(idParam as LocationId, { name, parentId });
			} else {
				await createLocation({ name, parentId: parentId ?? undefined });
			}
			navigate("/locations");
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

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
					to="/locations"
					startIcon={<ArrowBackIcon />}
				>
					Back to locations
				</Button>
			</Box>
		);
	}

	return (
		<Box sx={shellSx}>
			<Breadcrumbs sx={{ mb: 2 }} aria-label="Breadcrumb">
				<Link
					component={RouterLink}
					to="/locations"
					underline="hover"
					color="inherit"
					variant="body2"
				>
					Locations
				</Link>
				<Typography color="text.primary" variant="body2">
					{isEdit ? "Edit location" : "New location"}
				</Typography>
			</Breadcrumbs>

			<Box sx={{ display: "flex", alignItems: "flex-start", gap: 1.5, mb: 2 }}>
				<IconButton
					component={RouterLink}
					to="/locations"
					aria-label="Back to locations"
					sx={{ mt: 0.25 }}
				>
					<ArrowBackIcon />
				</IconButton>
				<Box sx={{ minWidth: 0 }}>
					<Typography variant="h5" sx={{ fontWeight: 700 }}>
						{isEdit ? "Edit location" : "New location"}
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
						Optional parent builds a hierarchy. You cannot choose this location
						or its descendants as the parent.
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

			<Paper elevation={0} sx={sectionPaperSx}>
				<Box
					component="form"
					onSubmit={(e) => {
						e.preventDefault();
						void submit();
					}}
					sx={{
						display: "flex",
						flexDirection: "column",
						gap: 2,
						maxWidth: 480,
					}}
				>
					<TextField
						label="Name"
						value={form.name}
						onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
						required
						fullWidth
						autoComplete="off"
					/>
					<FormControl fullWidth>
						<InputLabel id="location-parent-label">Parent</InputLabel>
						<Select
							labelId="location-parent-label"
							label="Parent"
							value={form.parentId}
							onChange={(e: SelectChangeEvent) =>
								setForm((f) => ({
									...f,
									parentId: e.target.value as LocationId | "",
								}))
							}
						>
							<MenuItem value="">
								<em>None (top level)</em>
							</MenuItem>
							{parentOptions.map((loc) => (
								<MenuItem key={loc.id} value={loc.id}>
									{loc.name}
								</MenuItem>
							))}
						</Select>
					</FormControl>
					<Box sx={{ display: "flex", gap: 1.5, flexWrap: "wrap", pt: 1 }}>
						<Button type="submit" variant="contained" disabled={saving}>
							{saving ? "Saving…" : "Save"}
						</Button>
						<Button component={RouterLink} to="/locations" disabled={saving}>
							Cancel
						</Button>
					</Box>
				</Box>
			</Paper>
		</Box>
	);
}
