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
import { useCallback, useEffect, useMemo, useState } from "react";
import { Link as RouterLink } from "react-router-dom";
import { deleteLocation, fetchLocations } from "../api/barcodileClient";
import { type LocationDto, parentIdOf } from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

export function LocationsPage() {
	const [rows, setRows] = useState<LocationDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<LocationDto | null>(null);
	const [deleting, setDeleting] = useState(false);

	const byId = useMemo(() => new Map(rows.map((r) => [r.id, r])), [rows]);

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchLocations();
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

	function parentName(row: LocationDto): string {
		const pid = parentIdOf(row);
		if (pid == null) {
			return "—";
		}
		return byId.get(pid)?.name ?? "—";
	}

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteLocation(deleteTarget.id);
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
					Locations
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					component={RouterLink}
					to="/locations/new"
				>
					Add location
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Storage hierarchy for inventory. Child locations keep their rows when a
				parent is removed; the parent link is cleared.
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
								<TableCell sx={{ fontWeight: 700 }}>Parent</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell>{row.name}</TableCell>
									<TableCell sx={{ color: "text.secondary" }}>
										{parentName(row)}
									</TableCell>
									<TableCell align="right">
										<IconButton
											aria-label={`Edit ${row.name}`}
											size="small"
											component={RouterLink}
											to={`/locations/${row.id}/edit`}
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
									<TableCell colSpan={3}>
										<Typography variant="body2" color="text.secondary">
											No locations yet.
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
				<DialogTitle>Delete location</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						Delete <strong>{deleteTarget?.name}</strong>? Inventory lines at
						this location are unlinked; sub-locations become top-level (parent
						cleared).
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
