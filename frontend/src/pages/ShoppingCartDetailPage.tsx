import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
import {
	Alert,
	Box,
	Breadcrumbs,
	Button,
	Chip,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	IconButton,
	Link,
	Paper,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	TextField,
	Typography,
} from "@mui/material";
import {
	type Dispatch,
	type SetStateAction,
	useCallback,
	useEffect,
	useMemo,
	useState,
} from "react";
import { Link as RouterLink, useNavigate, useParams } from "react-router-dom";
import {
	createShoppingCartLine,
	deleteShoppingCart,
	deleteShoppingCartLine,
	fetchShoppingCart,
	fetchShoppingCartFromProvider,
	updateShoppingCart,
	updateShoppingCartLine,
} from "../api/barcodileClient";
import { CatalogItemSearchInput } from "../components/CatalogItemSearchInput";
import {
	type CatalogItemDto,
	type CatalogItemId,
	isStoredShoppingCartId,
	type ShoppingCartDto,
	type ShoppingCartLineDto,
} from "../domain/barcodile";

const shellSx = {
	maxWidth: 960,
	mx: "auto",
} as const;

const sectionPaperSx = {
	p: { xs: 2, sm: 2.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
} as const;

export function ShoppingCartDetailPage() {
	const { id: idParam } = useParams<{ id: string }>();
	const navigate = useNavigate();
	const cartId = idParam ?? "";
	const cartIsStored = isStoredShoppingCartId(cartId);
	const isPicnicCart = cartId === "picnic";

	const [cart, setCart] = useState<ShoppingCartDto | null>(null);
	const [nameDraft, setNameDraft] = useState("");
	const [loading, setLoading] = useState(true);
	const [loadError, setLoadError] = useState<string | null>(null);
	const [actionError, setActionError] = useState<string | null>(null);
	const [savingName, setSavingName] = useState(false);
	const [deleteOpen, setDeleteOpen] = useState(false);
	const [deleting, setDeleting] = useState(false);

	const [qtyDraft, setQtyDraft] = useState<Record<string, string>>({});
	const [lineBusyId, setLineBusyId] = useState<string | null>(null);

	const load = useCallback(async () => {
		if (!cartId) {
			return;
		}
		setLoadError(null);
		setLoading(true);
		try {
			const row = !cartIsStored
				? await fetchShoppingCartFromProvider(cartId)
				: await fetchShoppingCart(cartId);
			setCart(row);
			setNameDraft(row.name ?? "");
			const nextQty: Record<string, string> = {};
			for (const line of row.lines ?? []) {
				nextQty[line.id] = String(line.quantity);
			}
			setQtyDraft(nextQty);
		} catch (e) {
			setCart(null);
			setLoadError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, [cartId, cartIsStored]);

	useEffect(() => {
		void load();
	}, [load]);

	const lines = useMemo(() => cart?.lines ?? [], [cart]);

	const handleCreateLine = useCallback(
		async (
			catalogItemId: CatalogItemId,
			quantity: number,
		): Promise<boolean> => {
			if (!cart) {
				return false;
			}
			setActionError(null);
			try {
				await createShoppingCartLine({
					shoppingCartId: cart.id,
					catalogItemId,
					quantity,
				});
				await load();
				return true;
			} catch (e) {
				setActionError(e instanceof Error ? e.message : "Add line failed");
				return false;
			}
		},
		[cart, load],
	);

	async function saveName() {
		if (!cart) {
			return;
		}
		setActionError(null);
		setSavingName(true);
		try {
			const trimmed = nameDraft.trim();
			await updateShoppingCart(cart.id, {
				name: trimmed === "" ? null : trimmed,
			});
			await load();
		} catch (e) {
			setActionError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSavingName(false);
		}
	}

	async function confirmDeleteCart() {
		if (!cart || !cartIsStored) {
			return;
		}
		setDeleting(true);
		setActionError(null);
		try {
			await deleteShoppingCart(cart.id);
			setDeleteOpen(false);
			navigate("/carts");
		} catch (e) {
			setActionError(e instanceof Error ? e.message : "Delete failed");
			setDeleteOpen(false);
		} finally {
			setDeleting(false);
		}
	}

	async function saveLineQty(line: ShoppingCartLineDto) {
		const raw = qtyDraft[line.id] ?? String(line.quantity);
		const q = Number.parseInt(raw, 10);
		if (!Number.isFinite(q) || q < 1) {
			setActionError("Quantity must be a positive whole number.");
			return;
		}
		if (q === line.quantity) {
			return;
		}
		setActionError(null);
		setLineBusyId(line.id);
		try {
			await updateShoppingCartLine(line.id, { quantity: q });
			await load();
		} catch (e) {
			setActionError(e instanceof Error ? e.message : "Update failed");
		} finally {
			setLineBusyId(null);
		}
	}

	async function removeLine(line: ShoppingCartLineDto) {
		setActionError(null);
		setLineBusyId(line.id);
		try {
			await deleteShoppingCartLine(line.id);
			await load();
		} catch (e) {
			setActionError(e instanceof Error ? e.message : "Remove failed");
		} finally {
			setLineBusyId(null);
		}
	}

	if (!cartId) {
		return (
			<Alert severity="warning" sx={{ borderRadius: 2 }}>
				Missing cart id.
			</Alert>
		);
	}

	return (
		<Box sx={shellSx}>
			<Breadcrumbs sx={{ mb: 2 }}>
				<Link
					component={RouterLink}
					to="/carts"
					underline="hover"
					color="inherit"
				>
					Carts
				</Link>
				<Typography color="text.primary">
					{cart?.name?.trim() ? cart.name : "Cart"}
				</Typography>
			</Breadcrumbs>

			{loadError ? (
				<Alert severity="error" sx={{ mb: 2, borderRadius: 2 }}>
					{loadError}
				</Alert>
			) : null}

			{loading || cart === null ? (
				<Typography variant="body2" color="text.secondary">
					{loading ? "Loading…" : ""}
				</Typography>
			) : (
				<StackedSections
					key={cartId}
					cart={cart}
					cartIsStored={cartIsStored}
					isPicnicCart={isPicnicCart}
					nameDraft={nameDraft}
					setNameDraft={setNameDraft}
					savingName={savingName}
					onSaveName={() => void saveName()}
					onOpenDelete={() => setDeleteOpen(true)}
					actionError={actionError}
					onDismissError={() => setActionError(null)}
					lines={lines}
					qtyDraft={qtyDraft}
					setQtyDraft={setQtyDraft}
					lineBusyId={lineBusyId}
					onSaveLineQty={(line) => void saveLineQty(line)}
					onRemoveLine={(line) => void removeLine(line)}
					onCreateLine={handleCreateLine}
					onAddFormError={(msg) => setActionError(msg)}
				/>
			)}

			<Box sx={{ mt: 2 }}>
				<Button
					component={RouterLink}
					to="/carts"
					startIcon={<ArrowBackIcon />}
				>
					All carts
				</Button>
			</Box>

			<Dialog
				open={deleteOpen}
				onClose={() => (deleting ? null : setDeleteOpen(false))}
			>
				<DialogTitle>Delete this cart?</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						This removes the cart and all line items. This cannot be undone.
					</Typography>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setDeleteOpen(false)} disabled={deleting}>
						Cancel
					</Button>
					<Button
						color="error"
						variant="contained"
						onClick={() => void confirmDeleteCart()}
						disabled={deleting}
					>
						Delete
					</Button>
				</DialogActions>
			</Dialog>
		</Box>
	);
}

type StackedProps = {
	cart: ShoppingCartDto;
	cartIsStored: boolean;
	isPicnicCart: boolean;
	nameDraft: string;
	setNameDraft: (v: string) => void;
	savingName: boolean;
	onSaveName: () => void;
	onOpenDelete: () => void;
	actionError: string | null;
	onDismissError: () => void;
	lines: ShoppingCartLineDto[];
	qtyDraft: Record<string, string>;
	setQtyDraft: Dispatch<SetStateAction<Record<string, string>>>;
	lineBusyId: string | null;
	onSaveLineQty: (line: ShoppingCartLineDto) => void;
	onRemoveLine: (line: ShoppingCartLineDto) => void;
	onCreateLine: (
		catalogItemId: CatalogItemId,
		quantity: number,
	) => Promise<boolean>;
	onAddFormError: (message: string) => void;
};

function StackedSections(props: StackedProps) {
	const {
		cart,
		cartIsStored,
		isPicnicCart,
		nameDraft,
		setNameDraft,
		savingName,
		onSaveName,
		onOpenDelete,
		actionError,
		onDismissError,
		lines,
		qtyDraft,
		setQtyDraft,
		lineBusyId,
		onSaveLineQty,
		onRemoveLine,
		onCreateLine,
		onAddFormError,
	} = props;

	const [addCatalogId, setAddCatalogId] = useState<CatalogItemId | "">("");
	const [addCatalogRow, setAddCatalogRow] = useState<CatalogItemDto | null>(
		null,
	);
	const [addQty, setAddQty] = useState("1");
	const [addingLine, setAddingLine] = useState(false);

	const picnicAddBlocked =
		isPicnicCart &&
		addCatalogRow != null &&
		!addCatalogRow.linkedPicnicProductId;

	async function addLine() {
		if (addCatalogId === "" || picnicAddBlocked) {
			return;
		}
		const q = Number.parseInt(addQty, 10);
		if (!Number.isFinite(q) || q < 1) {
			onAddFormError("Quantity must be a positive whole number.");
			return;
		}
		setAddingLine(true);
		try {
			const ok = await onCreateLine(addCatalogId, q);
			if (ok) {
				setAddCatalogId("");
				setAddCatalogRow(null);
				setAddQty("1");
			}
		} finally {
			setAddingLine(false);
		}
	}

	return (
		<Box sx={{ display: "flex", flexDirection: "column", gap: 2 }}>
			<Paper elevation={0} sx={sectionPaperSx}>
				<Box
					sx={{
						display: "flex",
						flexWrap: "wrap",
						alignItems: "center",
						gap: 1,
						mb: 2,
					}}
				>
					<Typography variant="h5" sx={{ fontWeight: 700, flexGrow: 1 }}>
						{cart.name?.trim() ? cart.name : "Unnamed cart"}
					</Typography>
					<Chip
						size="small"
						label={cartIsStored ? "Barcodile cart" : "Provider cart"}
						color="default"
						variant="outlined"
					/>
				</Box>
				{actionError ? (
					<Alert severity="error" sx={{ mb: 2 }} onClose={onDismissError}>
						{actionError}
					</Alert>
				) : null}
				<Typography
					variant="caption"
					color="text.secondary"
					sx={{ display: "block", mb: 1.5 }}
				>
					Created {new Date(cart.createdAt).toLocaleString()} · id {cart.id}
				</Typography>
				<Box
					sx={{
						display: "flex",
						flexWrap: "wrap",
						gap: 2,
						alignItems: "flex-start",
					}}
				>
					<TextField
						label="Name"
						value={nameDraft}
						onChange={(e) => setNameDraft(e.target.value)}
						sx={{ minWidth: 260, flex: 1 }}
					/>
					<Button variant="outlined" onClick={onSaveName} disabled={savingName}>
						Save name
					</Button>
					<Button
						color="error"
						variant="outlined"
						onClick={onOpenDelete}
						disabled={!cartIsStored}
					>
						Delete cart
					</Button>
				</Box>
			</Paper>

			<Paper elevation={0} sx={sectionPaperSx}>
				<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 2 }}>
					Line items
				</Typography>
				<Box
					sx={{
						display: "flex",
						flexWrap: "wrap",
						gap: 2,
						alignItems: "flex-start",
						mb: 2,
					}}
				>
					<Box sx={{ flex: "1 1 280px", minWidth: 0 }}>
						<CatalogItemSearchInput
							value={addCatalogId}
							onChange={(id, row) => {
								setAddCatalogId(id);
								setAddCatalogRow(row);
							}}
							label="Add catalog item"
						/>
					</Box>
					<TextField
						label="Qty"
						type="number"
						value={addQty}
						onChange={(e) => setAddQty(e.target.value)}
						slotProps={{ htmlInput: { min: 1, step: 1 } }}
						sx={{ width: 100 }}
					/>
					<Button
						variant="contained"
						onClick={() => void addLine()}
						disabled={addingLine || addCatalogId === "" || picnicAddBlocked}
					>
						Add line
					</Button>
				</Box>
				{isPicnicCart ? (
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Only catalog items linked to a Picnic product can be added to this
						basket.
					</Typography>
				) : null}
				<TableContainer>
					<Table size="small">
						<TableHead>
							<TableRow>
								<TableCell sx={{ fontWeight: 700 }}>Item</TableCell>
								<TableCell sx={{ fontWeight: 700, width: 120 }}>Qty</TableCell>
								<TableCell align="right" sx={{ fontWeight: 700, width: 140 }}>
									Actions
								</TableCell>
							</TableRow>
						</TableHead>
						<TableBody>
							{lines.length === 0 ? (
								<TableRow>
									<TableCell colSpan={3}>
										<Typography variant="body2" color="text.secondary">
											No lines yet.
										</Typography>
									</TableCell>
								</TableRow>
							) : (
								lines.map((line) => {
									const busy = lineBusyId === line.id;
									return (
										<TableRow key={line.id} hover>
											<TableCell>
												{line.catalogItem?.name ?? line.catalogItem?.id ?? "—"}
											</TableCell>
											<TableCell>
												<TextField
													type="number"
													size="small"
													value={qtyDraft[line.id] ?? String(line.quantity)}
													onChange={(e) =>
														setQtyDraft((prev) => ({
															...prev,
															[line.id]: e.target.value,
														}))
													}
													disabled={busy}
													slotProps={{ htmlInput: { min: 1, step: 1 } }}
													sx={{ width: 88 }}
												/>
											</TableCell>
											<TableCell align="right">
												<Button
													size="small"
													onClick={() => onSaveLineQty(line)}
													disabled={busy}
												>
													Update
												</Button>
												<IconButton
													size="small"
													color="error"
													aria-label="Remove line"
													onClick={() => onRemoveLine(line)}
													disabled={busy}
												>
													<DeleteOutlinedIcon fontSize="small" />
												</IconButton>
											</TableCell>
										</TableRow>
									);
								})
							)}
						</TableBody>
					</Table>
				</TableContainer>
			</Paper>
		</Box>
	);
}
