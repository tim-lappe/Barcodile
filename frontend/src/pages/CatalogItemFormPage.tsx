import AddIcon from "@mui/icons-material/Add";
import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import AutorenewIcon from "@mui/icons-material/Autorenew";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
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
	Switch,
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableRow,
	TextField,
	Typography,
} from "@mui/material";
import {
	type ChangeEvent,
	useCallback,
	useEffect,
	useRef,
	useState,
} from "react";
import {
	Link as RouterLink,
	useNavigate,
	useParams,
	useSearchParams,
} from "react-router-dom";
import {
	catalogItemImageUrl,
	createCartStockAutomationRule,
	createCatalogItem,
	deleteCartStockAutomationRule,
	deleteCatalogItemImage,
	fetchCartStockAutomationRules,
	fetchCatalogItem,
	fetchPicnicCatalogProductSummary,
	fetchShoppingCarts,
	patchCartStockAutomationRule,
	postRecreateCatalogItemFromBarcode,
	updateCatalogItem,
	uploadCatalogItemImage,
} from "../api/barcodileClient";
import {
	CATALOG_ITEM_PICNIC_PRODUCT_QUERY,
	catalogItemCreationSourceDef,
	parseCatalogItemCreationSource,
} from "../catalog/catalogItemCreationSources";
import {
	CATALOG_ITEM_ATTRIBUTE_OPTIONS,
	type CartStockAutomationRuleDto,
	type CatalogItemAttributeKey,
	type CatalogItemDto,
	type CatalogItemId,
	type ShoppingCartDto,
	type ShoppingCartId,
	type VolumeDto,
	type VolumeUnit,
	type WeightDto,
	type WeightUnit,
} from "../domain/barcodile";
import { usePicnicConnection } from "../picnic/usePicnicConnection";

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

type AttributeFormRow = {
	clientId: string;
	serverId?: string;
	attribute: CatalogItemAttributeKey;
	valueText: string;
};

type FormState = {
	name: string;
	attributeRows: AttributeFormRow[];
	barcodeCode: string;
	barcodeType: string;
	volumeAmount: string;
	volumeUnit: "" | VolumeUnit;
	weightAmount: string;
	weightUnit: "" | WeightUnit;
	picnicLinkedProductId: string;
};

function newClientId(): string {
	return crypto.randomUUID();
}

function valueTextFromDto(value: unknown | null | undefined): string {
	if (value === null || value === undefined) {
		return "";
	}
	return typeof value === "number" ? String(value) : String(value);
}

function parseAttributeValueText(
	attribute: CatalogItemAttributeKey,
	raw: string,
): { ok: true; value: unknown | null } | { ok: false; message: string } {
	const t = raw.trim();
	if (t === "") {
		return { ok: true, value: null };
	}
	if (attribute === "alcohol_percent") {
		const n = Number(t.replace(",", "."));
		if (Number.isNaN(n)) {
			return { ok: false, message: "Alcohol % must be a valid number." };
		}
		return { ok: true, value: n };
	}
	return { ok: true, value: t };
}

function shoppingCartIdFromIri(iri: string): ShoppingCartId {
	const p = "/api/shopping_carts/";
	return iri.startsWith(p) ? iri.slice(p.length) : iri;
}

function shoppingCartMenuLabel(c: ShoppingCartDto): string {
	return c.name != null && c.name.trim() !== ""
		? c.name
		: `Cart ${c.id.slice(0, 8)}…`;
}

function firstUnusedAttribute(
	used: Set<CatalogItemAttributeKey>,
): CatalogItemAttributeKey | null {
	for (const o of CATALOG_ITEM_ATTRIBUTE_OPTIONS) {
		if (!used.has(o.value)) {
			return o.value;
		}
	}
	return null;
}

function volumeFromForm(f: FormState): VolumeDto | null {
	const a = f.volumeAmount.trim();
	if (!a || !f.volumeUnit) {
		return null;
	}
	return { amount: a, unit: f.volumeUnit };
}

function weightFromForm(f: FormState): WeightDto | null {
	const a = f.weightAmount.trim();
	if (!a || !f.weightUnit) {
		return null;
	}
	return { amount: a, unit: f.weightUnit };
}

function emptyForm(): FormState {
	return {
		name: "",
		attributeRows: [],
		barcodeCode: "",
		barcodeType: "EAN",
		volumeAmount: "",
		volumeUnit: "",
		weightAmount: "",
		weightUnit: "",
		picnicLinkedProductId: "",
	};
}

function dtoToForm(row: CatalogItemDto): FormState {
	const links = row.catalogItemAttributes ?? [];
	const v = row.volume;
	const w = row.weight;
	const b = row.barcode;
	return {
		name: row.name,
		attributeRows: links.map((l) => ({
			clientId: l.id,
			serverId: l.id,
			attribute: l.attribute as CatalogItemAttributeKey,
			valueText: valueTextFromDto(l.value),
		})),
		barcodeCode: b?.code ?? "",
		barcodeType: b?.type ?? "EAN",
		volumeAmount: v ? v.amount : "",
		volumeUnit: v ? v.unit : "",
		weightAmount: w ? w.amount : "",
		weightUnit: w ? w.unit : "",
		picnicLinkedProductId: row.linkedPicnicProductId ?? "",
	};
}

export function CatalogItemFormPage() {
	const { id: idParam } = useParams<{ id: string }>();
	const navigate = useNavigate();
	const [searchParams] = useSearchParams();
	const isEdit = Boolean(idParam);
	const { picnicConnected, picnicStatusLoading } = usePicnicConnection();

	const creationSource = isEdit
		? ("manual" as const)
		: parseCatalogItemCreationSource(searchParams.get("source"));
	const creationSourceDef = catalogItemCreationSourceDef(creationSource);

	const imageFileInputRef = useRef<HTMLInputElement | null>(null);
	const picnicSectionRef = useRef<HTMLDivElement | null>(null);
	const picnicIntroScrollDoneRef = useRef(false);
	const [form, setForm] = useState<FormState>(emptyForm);
	const [loadError, setLoadError] = useState<string | null>(null);
	const [loading, setLoading] = useState(isEdit);
	const [formError, setFormError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [imageFileName, setImageFileName] = useState<string | null>(null);
	const [imageNonce, setImageNonce] = useState(0);
	const [imageBusy, setImageBusy] = useState(false);
	const [imageError, setImageError] = useState<string | null>(null);
	const [pendingImageFile, setPendingImageFile] = useState<File | null>(null);
	const [pendingImagePreviewUrl, setPendingImagePreviewUrl] = useState<
		string | null
	>(null);
	const [automationRules, setAutomationRules] = useState<
		CartStockAutomationRuleDto[]
	>([]);
	const [cartsForAutomation, setCartsForAutomation] = useState<
		ShoppingCartDto[]
	>([]);
	const [automationError, setAutomationError] = useState<string | null>(null);
	const [newRuleCartId, setNewRuleCartId] = useState("");
	const [newRuleStockBelow, setNewRuleStockBelow] = useState("5");
	const [newRuleAddQty, setNewRuleAddQty] = useState("1");
	const [addingRule, setAddingRule] = useState(false);
	const [recreateFromEanBusy, setRecreateFromEanBusy] = useState(false);

	const loadExisting = useCallback(async () => {
		if (!idParam) {
			return;
		}
		setLoadError(null);
		setLoading(true);
		try {
			const row = await fetchCatalogItem(idParam as CatalogItemId);
			setForm(dtoToForm(row));
			setImageFileName(row.imageFileName ?? null);
			setImageNonce((n) => n + 1);
			setAutomationError(null);
			try {
				const [rules, cartList] = await Promise.all([
					fetchCartStockAutomationRules(idParam as CatalogItemId),
					fetchShoppingCarts(),
				]);
				setAutomationRules(rules);
				setCartsForAutomation(cartList);
			} catch (ae) {
				setAutomationError(
					ae instanceof Error
						? ae.message
						: "Failed to load cart automation rules",
				);
				setAutomationRules([]);
				setCartsForAutomation([]);
			}
		} catch (e) {
			setLoadError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, [idParam]);

	useEffect(() => {
		if (isEdit) {
			setPendingImageFile(null);
			setPendingImagePreviewUrl((prev) => {
				if (prev) {
					URL.revokeObjectURL(prev);
				}
				return null;
			});
			void loadExisting();
		} else {
			setForm(emptyForm());
			setImageFileName(null);
			setImageNonce(0);
			setImageError(null);
			setPendingImageFile(null);
			setPendingImagePreviewUrl((prev) => {
				if (prev) {
					URL.revokeObjectURL(prev);
				}
				return null;
			});
			setAutomationRules([]);
			setCartsForAutomation([]);
			setAutomationError(null);
			setNewRuleCartId("");
			setNewRuleStockBelow("5");
			setNewRuleAddQty("1");
			setLoading(false);
			setLoadError(null);
		}
	}, [isEdit, loadExisting]);

	useEffect(() => {
		if (isEdit) {
			return;
		}
		const pid =
			searchParams.get(CATALOG_ITEM_PICNIC_PRODUCT_QUERY)?.trim() ?? "";
		if (pid === "") {
			return;
		}
		setForm((f) => ({ ...f, picnicLinkedProductId: pid }));
		let cancelled = false;
		void (async () => {
			try {
				const p = await fetchPicnicCatalogProductSummary(pid);
				if (cancelled) {
					return;
				}
				setForm((f) => ({
					...f,
					picnicLinkedProductId: pid,
					name:
						f.name.trim() === "" && p.name.trim() !== ""
							? p.name.trim()
							: f.name,
				}));
			} catch {
				if (!cancelled) {
					setFormError(
						"Could not load Picnic product details. You can still edit the Picnic product id manually.",
					);
				}
			} finally {
				if (!cancelled) {
					const next = new URLSearchParams(searchParams);
					next.delete(CATALOG_ITEM_PICNIC_PRODUCT_QUERY);
					const suffix = next.toString() ? `?${next.toString()}` : "";
					navigate(`/catalog-items/new${suffix}`, { replace: true });
				}
			}
		})();
		return () => {
			cancelled = true;
		};
	}, [isEdit, searchParams, navigate]);

	useEffect(() => {
		if (creationSource !== "picnic") {
			picnicIntroScrollDoneRef.current = false;
		}
	}, [creationSource]);

	useEffect(() => {
		if (isEdit || creationSource !== "picnic" || picnicStatusLoading) {
			return;
		}
		if (picnicIntroScrollDoneRef.current) {
			return;
		}
		picnicIntroScrollDoneRef.current = true;
		const t = window.setTimeout(() => {
			picnicSectionRef.current?.scrollIntoView({
				behavior: "smooth",
				block: "center",
			});
		}, 160);
		return () => window.clearTimeout(t);
	}, [creationSource, isEdit, picnicStatusLoading]);

	async function onImageFileSelected(e: ChangeEvent<HTMLInputElement>) {
		const file = e.target.files?.[0];
		e.target.value = "";
		if (!file) {
			return;
		}
		setImageError(null);
		if (!isEdit || !idParam) {
			setPendingImagePreviewUrl((prev) => {
				if (prev) {
					URL.revokeObjectURL(prev);
				}
				return URL.createObjectURL(file);
			});
			setPendingImageFile(file);
			return;
		}
		setImageBusy(true);
		try {
			const updated = await uploadCatalogItemImage(
				idParam as CatalogItemId,
				file,
			);
			setImageFileName(updated.imageFileName ?? null);
			setImageNonce((n) => n + 1);
		} catch (err) {
			setImageError(err instanceof Error ? err.message : "Upload failed");
		} finally {
			setImageBusy(false);
		}
	}

	async function removeImage() {
		setImageError(null);
		if (!isEdit || !idParam) {
			setPendingImagePreviewUrl((prev) => {
				if (prev) {
					URL.revokeObjectURL(prev);
				}
				return null;
			});
			setPendingImageFile(null);
			return;
		}
		setImageBusy(true);
		try {
			const updated = await deleteCatalogItemImage(idParam as CatalogItemId);
			setImageFileName(updated.imageFileName ?? null);
			setImageNonce((n) => n + 1);
		} catch (err) {
			setImageError(err instanceof Error ? err.message : "Remove failed");
		} finally {
			setImageBusy(false);
		}
	}

	function addAttributeRow() {
		const used = new Set(form.attributeRows.map((r) => r.attribute));
		const next = firstUnusedAttribute(used);
		if (next === null) {
			return;
		}
		setForm((f) => ({
			...f,
			attributeRows: [
				...f.attributeRows,
				{ clientId: newClientId(), attribute: next, valueText: "" },
			],
		}));
	}

	async function refreshAutomationRulesOnly() {
		if (!idParam) {
			return;
		}
		const rules = await fetchCartStockAutomationRules(idParam as CatalogItemId);
		setAutomationRules(rules);
	}

	async function patchAutomationRule(
		rule: CartStockAutomationRuleDto,
		patch: {
			shoppingCartId?: ShoppingCartId;
			stockBelow?: number;
			addQuantity?: number;
			enabled?: boolean;
		},
	) {
		if (!idParam) {
			return;
		}
		setAutomationError(null);
		try {
			await patchCartStockAutomationRule(
				idParam as CatalogItemId,
				rule.id,
				patch,
			);
			await refreshAutomationRulesOnly();
		} catch (e) {
			setAutomationError(e instanceof Error ? e.message : "Update failed");
		}
	}

	async function onRuleStockBlur(
		rule: CartStockAutomationRuleDto,
		raw: string,
	) {
		const n = Number.parseInt(raw.trim(), 10);
		if (Number.isNaN(n) || n < 0) {
			setAutomationError(
				"Stock threshold must be a whole number zero or greater.",
			);
			return;
		}
		if (n === rule.stockBelow) {
			return;
		}
		await patchAutomationRule(rule, { stockBelow: n });
	}

	async function onRuleAddQtyBlur(
		rule: CartStockAutomationRuleDto,
		raw: string,
	) {
		const n = Number.parseInt(raw.trim(), 10);
		if (Number.isNaN(n) || n < 1) {
			setAutomationError("Add quantity must be a whole number at least 1.");
			return;
		}
		if (n === rule.addQuantity) {
			return;
		}
		await patchAutomationRule(rule, { addQuantity: n });
	}

	async function onAddAutomationRule() {
		if (!idParam) {
			return;
		}
		if (!newRuleCartId) {
			setAutomationError("Choose a shopping cart for the new rule.");
			return;
		}
		const sb = Number.parseInt(newRuleStockBelow.trim(), 10);
		const aq = Number.parseInt(newRuleAddQty.trim(), 10);
		if (Number.isNaN(sb) || sb < 0) {
			setAutomationError(
				"Stock threshold must be a whole number zero or greater.",
			);
			return;
		}
		if (Number.isNaN(aq) || aq < 1) {
			setAutomationError("Add quantity must be a whole number at least 1.");
			return;
		}
		setAddingRule(true);
		setAutomationError(null);
		try {
			await createCartStockAutomationRule({
				catalogItemId: idParam as CatalogItemId,
				shoppingCartId: newRuleCartId as ShoppingCartId,
				stockBelow: sb,
				addQuantity: aq,
				enabled: true,
			});
			setNewRuleStockBelow("5");
			setNewRuleAddQty("1");
			await refreshAutomationRulesOnly();
		} catch (e) {
			setAutomationError(
				e instanceof Error ? e.message : "Could not create rule",
			);
		} finally {
			setAddingRule(false);
		}
	}

	async function onDeleteAutomationRule(rule: CartStockAutomationRuleDto) {
		if (!idParam) {
			return;
		}
		setAutomationError(null);
		try {
			await deleteCartStockAutomationRule(idParam as CatalogItemId, rule.id);
			await refreshAutomationRulesOnly();
		} catch (e) {
			setAutomationError(e instanceof Error ? e.message : "Delete failed");
		}
	}

	async function onRecreateFromEan() {
		if (!idParam) {
			return;
		}
		const code = form.barcodeCode.trim();
		const sym = (form.barcodeType.trim() || "EAN").toLowerCase();
		if (!code || sym !== "ean") {
			return;
		}
		setFormError(null);
		setRecreateFromEanBusy(true);
		try {
			const dto = await postRecreateCatalogItemFromBarcode(
				idParam as CatalogItemId,
			);
			setForm(dtoToForm(dto));
			setImageFileName(dto.imageFileName ?? null);
			setImageNonce((n) => n + 1);
		} catch (e) {
			setFormError(
				e instanceof Error ? e.message : "Could not recreate from EAN.",
			);
		} finally {
			setRecreateFromEanBusy(false);
		}
	}

	async function submitForm() {
		setFormError(null);
		const name = form.name.trim();
		if (!name) {
			setFormError("Name is required.");
			return;
		}
		const keys = form.attributeRows.map((r) => r.attribute);
		if (new Set(keys).size !== keys.length) {
			setFormError("Each attribute can only appear once.");
			return;
		}
		const parsedRows: { row: AttributeFormRow; value: unknown | null }[] = [];
		for (const r of form.attributeRows) {
			const parsed = parseAttributeValueText(r.attribute, r.valueText);
			if (parsed.ok === false) {
				setFormError(parsed.message);
				return;
			}
			parsedRows.push({ row: r, value: parsed.value });
		}
		const volume = volumeFromForm(form);
		const weight = weightFromForm(form);
		const barcodeCode = form.barcodeCode.trim();
		const barcodeType = form.barcodeType.trim() || "EAN";
		const picnicLinkedProductId = form.picnicLinkedProductId.trim() || null;
		const catalogItemAttributesPayload = parsedRows.map(
			({ row: r, value: v }) => ({
				...(r.serverId ? { id: r.serverId } : {}),
				attribute: r.attribute,
				value: v,
			}),
		);
		setSaving(true);
		try {
			if (isEdit && idParam) {
				await updateCatalogItem(idParam as CatalogItemId, {
					name,
					volume,
					weight,
					barcode: barcodeCode
						? { code: barcodeCode, type: barcodeType }
						: null,
					catalogItemAttributes: catalogItemAttributesPayload,
					linkedPicnicProductId: picnicLinkedProductId,
				});
			} else {
				const created = await createCatalogItem({
					name,
					volume,
					weight,
					...(barcodeCode
						? { barcode: { code: barcodeCode, type: barcodeType } }
						: {}),
					catalogItemAttributes: catalogItemAttributesPayload,
					linkedPicnicProductId: picnicLinkedProductId,
					creationSource,
				});
				if (pendingImageFile) {
					try {
						await uploadCatalogItemImage(created.id, pendingImageFile);
					} catch (uploadErr) {
						setPendingImagePreviewUrl((prev) => {
							if (prev) {
								URL.revokeObjectURL(prev);
							}
							return null;
						});
						setPendingImageFile(null);
						setFormError(
							uploadErr instanceof Error
								? `Catalog item was saved but the image could not be uploaded: ${uploadErr.message}`
								: "Catalog item was saved but the image could not be uploaded.",
						);
						navigate(`/catalog-items/${created.id}/edit`);
						return;
					}
				}
			}
			navigate("/catalog-items");
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	const canAddMoreAttributes =
		form.attributeRows.length < CATALOG_ITEM_ATTRIBUTE_OPTIONS.length;

	const imagePreviewSrc =
		isEdit && idParam && imageFileName
			? catalogItemImageUrl(
					idParam as CatalogItemId,
					`${imageFileName}-${imageNonce}`,
				)
			: pendingImagePreviewUrl;

	if (loading) {
		return (
			<Box sx={shellSx}>
				<Typography color="text.secondary">Loading…</Typography>
			</Box>
		);
	}

	if (isEdit && loadError) {
		return (
			<Box sx={shellSx}>
				<Alert severity="error" sx={{ mb: 2 }}>
					{loadError}
				</Alert>
				<Button
					component={RouterLink}
					to="/catalog-items"
					startIcon={<ArrowBackIcon />}
				>
					Back to catalog items
				</Button>
			</Box>
		);
	}

	return (
		<Box sx={shellSx}>
			<Breadcrumbs sx={{ mb: 2 }} aria-label="Breadcrumb">
				<Link
					component={RouterLink}
					to="/catalog-items"
					underline="hover"
					color="inherit"
					variant="body2"
				>
					Catalog items
				</Link>
				<Typography color="text.primary" variant="body2">
					{isEdit ? "Edit catalog item" : creationSourceDef.formTitle}
				</Typography>
			</Breadcrumbs>

			<Box sx={{ display: "flex", alignItems: "flex-start", gap: 1.5, mb: 2 }}>
				<IconButton
					component={RouterLink}
					to="/catalog-items"
					aria-label="Back to catalog items"
					sx={{ mt: 0.25 }}
				>
					<ArrowBackIcon />
				</IconButton>
				<Box sx={{ minWidth: 0 }}>
					<Typography variant="h5" sx={{ fontWeight: 700 }}>
						{isEdit ? "Edit catalog item" : creationSourceDef.formTitle}
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
						{isEdit
							? "Update the template, default sizing, catalog item attributes, or barcode."
							: creationSourceDef.formSubtitle}
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
				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 2 }}>
						Basics
					</Typography>
					<TextField
						label="Name"
						value={form.name}
						onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
						required
						fullWidth
					/>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Image
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						JPEG, PNG, WebP, or GIF. Shown in lists and helps identify the
						product at a glance.
						{!isEdit
							? " On a new catalog item, the file is sent when you save."
							: null}
					</Typography>
					<input
						ref={imageFileInputRef}
						type="file"
						hidden
						accept="image/jpeg,image/png,image/webp,image/gif"
						onChange={(e) => void onImageFileSelected(e)}
					/>
					{imageError && (
						<Alert
							severity="error"
							sx={{ mb: 2 }}
							onClose={() => setImageError(null)}
						>
							{imageError}
						</Alert>
					)}
					<Box
						sx={{
							display: "flex",
							flexWrap: "wrap",
							gap: 2,
							alignItems: "flex-start",
							mb: 2,
						}}
					>
						<Box
							sx={{
								width: 200,
								height: 200,
								borderRadius: 1,
								border: "1px solid",
								borderColor: "divider",
								bgcolor: "action.hover",
								display: "flex",
								alignItems: "center",
								justifyContent: "center",
								overflow: "hidden",
							}}
						>
							{imagePreviewSrc ? (
								<Box
									component="img"
									src={imagePreviewSrc}
									alt=""
									sx={{
										maxWidth: "100%",
										maxHeight: "100%",
										objectFit: "contain",
									}}
								/>
							) : (
								<Typography
									variant="body2"
									color="text.secondary"
									sx={{ px: 2, textAlign: "center" }}
								>
									No image
								</Typography>
							)}
						</Box>
						<Box
							sx={{
								display: "flex",
								flexDirection: "column",
								gap: 1,
								alignItems: "flex-start",
							}}
						>
							<Button
								variant="outlined"
								size="small"
								disabled={imageBusy}
								onClick={() => imageFileInputRef.current?.click()}
							>
								{imageBusy
									? "Working…"
									: isEdit
										? imageFileName
											? "Replace image"
											: "Upload image"
										: pendingImageFile
											? "Replace image"
											: "Upload image"}
							</Button>
							{(isEdit && imageFileName) || (!isEdit && pendingImageFile) ? (
								<Button
									variant="text"
									size="small"
									color="error"
									disabled={imageBusy}
									onClick={() => void removeImage()}
								>
									Remove image
								</Button>
							) : null}
						</Box>
					</Box>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Default volume and weight
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Optional defaults for new inventory lines. Leave blank if sizes vary
						per batch.
					</Typography>
					<Typography variant="subtitle2" color="text.secondary" sx={{ mb: 1 }}>
						Volume
					</Typography>
					<Box sx={{ display: "flex", gap: 1, flexWrap: "wrap", mb: 2 }}>
						<TextField
							label="Amount"
							value={form.volumeAmount}
							onChange={(e) =>
								setForm((f) => ({ ...f, volumeAmount: e.target.value }))
							}
							sx={{ flex: 1, minWidth: 140 }}
							slotProps={{
								htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
							}}
						/>
						<FormControl sx={{ minWidth: 140 }}>
							<InputLabel id="vol-unit-label">Unit</InputLabel>
							<Select<"" | VolumeUnit>
								labelId="vol-unit-label"
								label="Unit"
								value={form.volumeUnit}
								onChange={(e: SelectChangeEvent<"" | VolumeUnit>) =>
									setForm((f) => ({
										...f,
										volumeUnit: e.target.value as "" | VolumeUnit,
									}))
								}
							>
								<MenuItem value="">
									<em>None</em>
								</MenuItem>
								<MenuItem value="ml">ml</MenuItem>
								<MenuItem value="l">l</MenuItem>
							</Select>
						</FormControl>
					</Box>
					<Typography variant="subtitle2" color="text.secondary" sx={{ mb: 1 }}>
						Weight
					</Typography>
					<Box sx={{ display: "flex", gap: 1, flexWrap: "wrap" }}>
						<TextField
							label="Amount"
							value={form.weightAmount}
							onChange={(e) =>
								setForm((f) => ({ ...f, weightAmount: e.target.value }))
							}
							sx={{ flex: 1, minWidth: 140 }}
							slotProps={{
								htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
							}}
						/>
						<FormControl sx={{ minWidth: 140 }}>
							<InputLabel id="w-unit-label">Unit</InputLabel>
							<Select<"" | WeightUnit>
								labelId="w-unit-label"
								label="Unit"
								value={form.weightUnit}
								onChange={(e: SelectChangeEvent<"" | WeightUnit>) =>
									setForm((f) => ({
										...f,
										weightUnit: e.target.value as "" | WeightUnit,
									}))
								}
							>
								<MenuItem value="">
									<em>None</em>
								</MenuItem>
								<MenuItem value="g">g</MenuItem>
								<MenuItem value="kg">kg</MenuItem>
							</Select>
						</FormControl>
					</Box>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Attributes
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Facts that are fixed for this product (for example ABV for a given
						bottling). They apply to every inventory line for this catalog item.
					</Typography>
					{form.attributeRows.map((row) => {
						const usedElsewhere = new Set(
							form.attributeRows
								.filter((x) => x.clientId !== row.clientId)
								.map((x) => x.attribute),
						);
						return (
							<Box
								key={row.clientId}
								sx={{
									display: "flex",
									gap: 1,
									alignItems: "center",
									flexWrap: "wrap",
									mb: 1.5,
									p: 1.5,
									borderRadius: 1,
									bgcolor: "action.hover",
								}}
							>
								<FormControl size="small" sx={{ minWidth: 220 }}>
									<InputLabel id={`attr-${row.clientId}`}>Attribute</InputLabel>
									<Select<CatalogItemAttributeKey>
										labelId={`attr-${row.clientId}`}
										label="Attribute"
										value={row.attribute}
										onChange={(
											e: SelectChangeEvent<CatalogItemAttributeKey>,
										) => {
											const v = e.target.value as CatalogItemAttributeKey;
											setForm((f) => ({
												...f,
												attributeRows: f.attributeRows.map((r) =>
													r.clientId === row.clientId
														? { ...r, attribute: v }
														: r,
												),
											}));
										}}
									>
										{CATALOG_ITEM_ATTRIBUTE_OPTIONS.map((o) => (
											<MenuItem
												key={o.value}
												value={o.value}
												disabled={
													usedElsewhere.has(o.value) &&
													o.value !== row.attribute
												}
											>
												{o.label}
											</MenuItem>
										))}
									</Select>
								</FormControl>
								<TextField
									size="small"
									label="Value"
									value={row.valueText}
									onChange={(e) =>
										setForm((f) => ({
											...f,
											attributeRows: f.attributeRows.map((r) =>
												r.clientId === row.clientId
													? { ...r, valueText: e.target.value }
													: r,
											),
										}))
									}
									sx={{ minWidth: 120, flex: 1 }}
									slotProps={{ htmlInput: { inputMode: "decimal" } }}
								/>
								<IconButton
									aria-label="Remove attribute"
									size="small"
									onClick={() =>
										setForm((f) => ({
											...f,
											attributeRows: f.attributeRows.filter(
												(r) => r.clientId !== row.clientId,
											),
										}))
									}
								>
									<DeleteOutlinedIcon fontSize="small" />
								</IconButton>
							</Box>
						);
					})}
					<Button
						variant="outlined"
						size="small"
						startIcon={<AddIcon />}
						onClick={addAttributeRow}
						disabled={!canAddMoreAttributes}
					>
						Add attribute
					</Button>
				</Paper>

				<Paper elevation={0} sx={sectionPaperSx}>
					<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Barcode
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						{isEdit
							? "Optionally set or change the code that identifies this catalog item."
							: "Optionally set a barcode together with this catalog item."}
					</Typography>
					<TextField
						label="Code"
						value={form.barcodeCode}
						onChange={(e) =>
							setForm((f) => ({ ...f, barcodeCode: e.target.value }))
						}
						fullWidth
						sx={{ mb: 2 }}
						slotProps={{
							htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
						}}
					/>
					<TextField
						label="Symbology"
						value={form.barcodeType}
						onChange={(e) =>
							setForm((f) => ({ ...f, barcodeType: e.target.value }))
						}
						fullWidth
					/>
					{isEdit &&
						form.barcodeCode.trim() !== "" &&
						(form.barcodeType.trim() || "EAN").toLowerCase() === "ean" && (
							<Box sx={{ mt: 2 }}>
								<Button
									variant="outlined"
									startIcon={<AutorenewIcon />}
									onClick={() => void onRecreateFromEan()}
									disabled={recreateFromEanBusy || saving}
								>
									{recreateFromEanBusy ? "Recreating…" : "Recreate with EAN"}
								</Button>
								<Typography
									variant="body2"
									color="text.secondary"
									sx={{ mt: 1 }}
								>
									Re-runs the barcode lookup and overwrites fields the model
									returns. The catalog item id and barcode are unchanged.
								</Typography>
							</Box>
						)}
				</Paper>

				{(!isEdit && creationSource === "picnic") ||
				(picnicConnected && !picnicStatusLoading) ? (
					<Paper elevation={0} sx={sectionPaperSx} ref={picnicSectionRef}>
						<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
							Picnic
						</Typography>
						<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
							Link this catalog item to a Picnic product id from the retailer
							catalog.
						</Typography>
						{picnicStatusLoading ? (
							<Typography variant="body2" color="text.secondary">
								Checking Picnic connection…
							</Typography>
						) : picnicConnected ? (
							<TextField
								label="Picnic product id (link)"
								value={form.picnicLinkedProductId}
								onChange={(e) =>
									setForm((f) => ({
										...f,
										picnicLinkedProductId: e.target.value,
									}))
								}
								fullWidth
								sx={{ mb: 2 }}
								helperText="The Picnic offer or product identifier you want associated with this catalog item."
								slotProps={{
									htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
								}}
							/>
						) : (
							<>
								<Alert severity="info" sx={{ mb: 2 }}>
									<Typography variant="body2" component="span">
										Connect your Picnic account under{" "}
										<Link component={RouterLink} to="/settings/picnic">
											Settings → Picnic
										</Link>{" "}
										to use live catalog data. You can still paste a product id
										below and save.
									</Typography>
								</Alert>
								<TextField
									label="Picnic product id (link)"
									value={form.picnicLinkedProductId}
									onChange={(e) =>
										setForm((f) => ({
											...f,
											picnicLinkedProductId: e.target.value,
										}))
									}
									fullWidth
									helperText="The Picnic offer or product identifier you want associated with this catalog item."
									slotProps={{
										htmlInput: {
											sx: { fontFamily: "ui-monospace, monospace" },
										},
									}}
								/>
							</>
						)}
					</Paper>
				) : null}

				{isEdit && idParam ? (
					<Paper elevation={0} sx={sectionPaperSx}>
						<Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
							Cart stock automation
						</Typography>
						<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
							When total inventory for this catalog item crosses from at or
							above the threshold to strictly below it, the listed quantity of
							this same catalog item is added to the selected shopping cart
							(merging an existing line if present). Only Barcodile shopping
							carts can be selected; provider baskets are not supported for
							automation.
						</Typography>
						{automationError ? (
							<Alert
								severity="error"
								sx={{ mb: 2 }}
								onClose={() => setAutomationError(null)}
							>
								{automationError}
							</Alert>
						) : null}
						<Table size="small" sx={{ mb: 2 }}>
							<TableHead>
								<TableRow>
									<TableCell>Shopping cart</TableCell>
									<TableCell width={140}>Stock below</TableCell>
									<TableCell width={120}>Add qty</TableCell>
									<TableCell width={100}>Enabled</TableCell>
									<TableCell width={56} />
								</TableRow>
							</TableHead>
							<TableBody>
								{automationRules.length === 0 ? (
									<TableRow>
										<TableCell colSpan={5}>
											<Typography variant="body2" color="text.secondary">
												No rules yet. Add one below.
											</Typography>
										</TableCell>
									</TableRow>
								) : (
									automationRules.map((rule) => (
										<TableRow key={rule.id}>
											<TableCell>
												<FormControl size="small" fullWidth>
													<Select
														value={shoppingCartIdFromIri(rule.shoppingCart)}
														onChange={(e: SelectChangeEvent) => {
															void patchAutomationRule(rule, {
																shoppingCartId: e.target
																	.value as ShoppingCartId,
															});
														}}
													>
														{cartsForAutomation.map((c) => (
															<MenuItem key={c.id} value={c.id}>
																{shoppingCartMenuLabel(c)}
															</MenuItem>
														))}
													</Select>
												</FormControl>
											</TableCell>
											<TableCell>
												<TextField
													size="small"
													key={`${rule.id}-sb-${rule.stockBelow}`}
													defaultValue={String(rule.stockBelow)}
													onBlur={(e) =>
														void onRuleStockBlur(rule, e.target.value)
													}
													fullWidth
													slotProps={{ htmlInput: { inputMode: "numeric" } }}
												/>
											</TableCell>
											<TableCell>
												<TextField
													size="small"
													key={`${rule.id}-aq-${rule.addQuantity}`}
													defaultValue={String(rule.addQuantity)}
													onBlur={(e) =>
														void onRuleAddQtyBlur(rule, e.target.value)
													}
													fullWidth
													slotProps={{ htmlInput: { inputMode: "numeric" } }}
												/>
											</TableCell>
											<TableCell>
												<Switch
													checked={rule.enabled}
													onChange={(_, v) =>
														void patchAutomationRule(rule, { enabled: v })
													}
													slotProps={{
														input: { "aria-label": "Rule enabled" },
													}}
												/>
											</TableCell>
											<TableCell>
												<IconButton
													aria-label="Delete rule"
													size="small"
													onClick={() => void onDeleteAutomationRule(rule)}
												>
													<DeleteOutlinedIcon fontSize="small" />
												</IconButton>
											</TableCell>
										</TableRow>
									))
								)}
							</TableBody>
						</Table>
						<Typography variant="subtitle2" sx={{ fontWeight: 600, mb: 1 }}>
							New rule
						</Typography>
						<Box
							sx={{
								display: "flex",
								flexWrap: "wrap",
								gap: 2,
								alignItems: "flex-start",
							}}
						>
							<FormControl size="small" sx={{ minWidth: 220 }}>
								<InputLabel id="new-rule-cart-label">Shopping cart</InputLabel>
								<Select
									labelId="new-rule-cart-label"
									label="Shopping cart"
									value={newRuleCartId}
									onChange={(e: SelectChangeEvent) =>
										setNewRuleCartId(e.target.value)
									}
								>
									<MenuItem value="">
										<em>Select cart</em>
									</MenuItem>
									{cartsForAutomation.map((c) => (
										<MenuItem key={c.id} value={c.id}>
											{shoppingCartMenuLabel(c)}
										</MenuItem>
									))}
								</Select>
							</FormControl>
							<TextField
								size="small"
								label="Stock below"
								value={newRuleStockBelow}
								onChange={(e) => setNewRuleStockBelow(e.target.value)}
								sx={{ width: 120 }}
								slotProps={{ htmlInput: { inputMode: "numeric" } }}
							/>
							<TextField
								size="small"
								label="Add qty"
								value={newRuleAddQty}
								onChange={(e) => setNewRuleAddQty(e.target.value)}
								sx={{ width: 100 }}
								slotProps={{ htmlInput: { inputMode: "numeric" } }}
							/>
							<Button
								variant="outlined"
								size="small"
								disabled={addingRule}
								onClick={() => void onAddAutomationRule()}
							>
								{addingRule ? "Adding…" : "Add rule"}
							</Button>
						</Box>
					</Paper>
				) : null}
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
					<Button component={RouterLink} to="/catalog-items" disabled={saving}>
						Cancel
					</Button>
					<Button
						variant="contained"
						onClick={() => void submitForm()}
						disabled={saving}
					>
						{saving
							? "Saving…"
							: isEdit
								? "Save changes"
								: "Create catalog item"}
					</Button>
				</Box>
			</Paper>
		</Box>
	);
}
