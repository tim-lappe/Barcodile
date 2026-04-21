import AddIcon from "@mui/icons-material/Add";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
import OpenInNewOutlinedIcon from "@mui/icons-material/OpenInNewOutlined";
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
	Tooltip,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useMemo, useState } from "react";
import { Link as RouterLink } from "react-router-dom";
import {
	deleteShoppingCart,
	fetchCartProviderIndex,
	fetchShoppingCarts,
} from "../api/barcodileClient";
import type {
	CartProviderIndexEntryDto,
	ShoppingCartDto,
} from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1100,
	mx: "auto",
} as const;

export function ShoppingCartsPage() {
	const [rows, setRows] = useState<ShoppingCartDto[]>([]);
	const [providerCarts, setProviderCarts] = useState<
		CartProviderIndexEntryDto[]
	>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [providerListError, setProviderListError] = useState<string | null>(
		null,
	);
	const [deleteTarget, setDeleteTarget] = useState<ShoppingCartDto | null>(
		null,
	);
	const [deleting, setDeleting] = useState(false);

	const load = useCallback(async () => {
		setListError(null);
		setProviderListError(null);
		setLoading(true);
		try {
			const list = await fetchShoppingCarts();
			const sorted = [...list].sort((a, b) => {
				const ta = new Date(a.createdAt).getTime();
				const tb = new Date(b.createdAt).getTime();
				return tb - ta;
			});
			setRows(sorted);
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		}
		try {
			const fromProviders = await fetchCartProviderIndex();
			setProviderCarts(fromProviders);
		} catch (e) {
			setProviderCarts([]);
			setProviderListError(
				e instanceof Error ? e.message : "Provider carts could not be loaded",
			);
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	const lineCount = useMemo(() => {
		return (cart: ShoppingCartDto) => cart.lines?.length ?? 0;
	}, []);

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteShoppingCart(deleteTarget.id);
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
					Carts
				</Typography>
				<Button
					variant="contained"
					startIcon={<AddIcon />}
					component={RouterLink}
					to="/carts/new"
				>
					New cart
				</Button>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Local carts stored in Barcodile. Carts from connected integrations
				appear in the same table when the server exposes them; they can be
				opened here but not edited or removed from this app.
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
			{providerListError ? (
				<Alert
					severity="warning"
					sx={{ mb: 2 }}
					onClose={() => setProviderListError(null)}
				>
					{providerListError}
				</Alert>
			) : null}
			{loading ? (
				<Typography variant="body2" color="text.secondary">
					Loading…
				</Typography>
			) : (
				<TableContainer>
					<Table size="small">
						<TableHead>
							<TableRow>
								<TableCell sx={{ fontWeight: 700 }}>Name</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Lines</TableCell>
								<TableCell sx={{ fontWeight: 700 }}>Created</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{providerCarts.map((entry) => (
								<TableRow key={`provider:${entry.id}`} hover>
									<TableCell>{entry.name.trim() ? entry.name : "—"}</TableCell>
									<TableCell>{entry.lineCount}</TableCell>
									<TableCell>
										{new Date(entry.createdAt).toLocaleString()}
									</TableCell>
									<TableCell align="right">
										<IconButton
											component={RouterLink}
											to={`/carts/${encodeURIComponent(entry.id)}`}
											size="small"
											aria-label={`Open ${entry.name}`}
											color="primary"
										>
											<OpenInNewOutlinedIcon fontSize="small" />
										</IconButton>
										<Tooltip title="Provider baskets cannot be deleted here.">
											<span>
												<IconButton
													size="small"
													disabled
													aria-label="Delete disabled"
												>
													<DeleteOutlinedIcon fontSize="small" />
												</IconButton>
											</span>
										</Tooltip>
									</TableCell>
								</TableRow>
							))}
							{rows.length === 0 && providerCarts.length === 0 ? (
								<TableRow>
									<TableCell colSpan={4}>
										<Typography variant="body2" color="text.secondary">
											No carts yet. Create one to start a list.
										</Typography>
									</TableCell>
								</TableRow>
							) : null}
							{rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell>{row.name?.trim() ? row.name : "—"}</TableCell>
									<TableCell>{lineCount(row)}</TableCell>
									<TableCell>
										{new Date(row.createdAt).toLocaleString()}
									</TableCell>
									<TableCell align="right">
										<IconButton
											component={RouterLink}
											to={`/carts/${row.id}`}
											size="small"
											aria-label="Open cart"
											color="primary"
										>
											<OpenInNewOutlinedIcon fontSize="small" />
										</IconButton>
										<IconButton
											size="small"
											aria-label="Delete cart"
											color="error"
											onClick={() => setDeleteTarget(row)}
										>
											<DeleteOutlinedIcon fontSize="small" />
										</IconButton>
									</TableCell>
								</TableRow>
							))}
						</TableBody>
					</Table>
				</TableContainer>
			)}

			<Dialog
				open={deleteTarget !== null}
				onClose={() => (deleting ? null : setDeleteTarget(null))}
			>
				<DialogTitle>Delete cart?</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						This removes the cart and all of its line items. This cannot be
						undone.
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
