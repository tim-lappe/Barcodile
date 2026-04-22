import CategoryOutlinedIcon from "@mui/icons-material/CategoryOutlined";
import {
	Autocomplete,
	Box,
	CircularProgress,
	TextField,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
	catalogItemImageUrl,
	fetchCatalogItem,
	searchCatalogItemsPicklist,
} from "../api/barcodileClient";
import type {
	CatalogItemAttributeKey,
	CatalogItemDto,
	CatalogItemId,
} from "../domain/barcodile";
import {
	catalogItemPickerLabel,
	formatCatalogItemAttributeSummary,
	formatVolumeShort,
	formatWeightShort,
} from "../domain/barcodile";

const defaultDebounceMs = 280;

function pickerSizeLine(row: CatalogItemDto): string {
	const v = formatVolumeShort(row.volume);
	const w = formatWeightShort(row.weight);
	const parts = [v, w].filter(Boolean);
	return parts.length ? parts.join(" · ") : "";
}

function pickerAttributesLine(row: CatalogItemDto): string {
	const links = row.catalogItemAttributes ?? [];
	if (links.length === 0) {
		return "";
	}
	const parts = links.map((l) =>
		formatCatalogItemAttributeSummary(
			l.attribute as CatalogItemAttributeKey,
			l.value,
		),
	);
	const s = parts.join(" · ");
	return s.length > 72 ? `${s.slice(0, 72)}…` : s;
}

function pickerBarcodeLine(row: CatalogItemDto): string {
	return row.barcode?.code ?? "";
}

export type CatalogItemSearchInputProps = {
	value: CatalogItemId | "";
	onChange: (id: CatalogItemId, selected: CatalogItemDto | null) => void;
	label?: string;
	required?: boolean;
	disabled?: boolean;
	error?: boolean;
	helperText?: string;
	fullWidth?: boolean;
	picklistLimit?: number;
	debounceMs?: number;
};

export function CatalogItemSearchInput({
	value,
	onChange,
	label = "Catalog item",
	required = false,
	disabled = false,
	error = false,
	helperText,
	fullWidth = true,
	picklistLimit = 50,
	debounceMs = defaultDebounceMs,
}: CatalogItemSearchInputProps) {
	const [options, setOptions] = useState<CatalogItemDto[]>([]);
	const [loading, setLoading] = useState(false);
	const [inputValue, setInputValue] = useState("");
	const [selected, setSelected] = useState<CatalogItemDto | null>(null);
	const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
	const searchSeq = useRef(0);
	const abortRef = useRef<AbortController | null>(null);
	const resolveToken = useRef(0);
	const onChangeRef = useRef(onChange);
	onChangeRef.current = onChange;

	useEffect(() => {
		let cancelled = false;
		const token = ++resolveToken.current;
		if (!value) {
			setSelected(null);
			setInputValue("");
			return;
		}
		(async () => {
			try {
				const row = await fetchCatalogItem(value);
				if (cancelled || token !== resolveToken.current) {
					return;
				}
				setSelected(row);
				setInputValue(catalogItemPickerLabel(row));
				onChangeRef.current(value, row);
			} catch {
				if (!cancelled && token === resolveToken.current) {
					setSelected(null);
				}
			}
		})();
		return () => {
			cancelled = true;
		};
	}, [value]);

	const applySearch = useCallback(
		async (term: string) => {
			const seq = ++searchSeq.current;
			abortRef.current?.abort();
			const ac = new AbortController();
			abortRef.current = ac;
			setLoading(true);
			try {
				const rows = await searchCatalogItemsPicklist(term, picklistLimit, {
					signal: ac.signal,
				});
				if (seq === searchSeq.current) {
					setOptions(rows);
				}
			} catch (e) {
				if (
					e &&
					typeof e === "object" &&
					"name" in e &&
					(e as { name: string }).name === "AbortError"
				) {
					return;
				}
				if (seq === searchSeq.current) {
					setOptions([]);
				}
			} finally {
				if (seq === searchSeq.current) {
					setLoading(false);
				}
			}
		},
		[picklistLimit],
	);

	useEffect(() => {
		if (debounceRef.current) {
			clearTimeout(debounceRef.current);
		}
		debounceRef.current = setTimeout(() => {
			debounceRef.current = null;
			void applySearch(inputValue);
		}, debounceMs);
		return () => {
			if (debounceRef.current) {
				clearTimeout(debounceRef.current);
			}
		};
	}, [inputValue, debounceMs, applySearch]);

	const mergedOptions = useMemo(() => {
		if (!selected) {
			return options;
		}
		if (options.some((o) => o.id === selected.id)) {
			return options;
		}
		return [selected, ...options];
	}, [options, selected]);

	return (
		<Autocomplete<CatalogItemDto, false, false, false>
			fullWidth={fullWidth}
			disabled={disabled}
			options={mergedOptions}
			value={selected}
			inputValue={inputValue}
			onInputChange={(_, v, reason) => {
				if (reason === "reset") {
					return;
				}
				setInputValue(v);
			}}
			onChange={(_, v) => {
				setSelected(v);
				if (v) {
					setInputValue(catalogItemPickerLabel(v));
				} else {
					setInputValue("");
				}
				onChange(v?.id ?? "", v);
			}}
			onOpen={() => {
				void applySearch(inputValue);
			}}
			loading={loading}
			getOptionLabel={(o) => catalogItemPickerLabel(o)}
			isOptionEqualToValue={(a, b) => a.id === b.id}
			filterOptions={(x) => x}
			noOptionsText="No matches"
			slotProps={{
				paper: {
					elevation: 6,
					sx: {
						mt: 0.75,
						borderRadius: 2,
						border: "1px solid",
						borderColor: "divider",
						overflow: "hidden",
						minWidth: 320,
					},
				},
				listbox: {
					sx: {
						py: 0.5,
						maxHeight: 360,
						"& .MuiAutocomplete-option": {
							minHeight: 0,
							px: 0,
							py: 0,
						},
					},
				},
			}}
			renderOption={(props, option, state) => {
				const codes = pickerBarcodeLine(option);
				const size = pickerSizeLine(option);
				const attrs = pickerAttributesLine(option);
				const metaParts = [codes, size].filter(Boolean);
				const meta = metaParts.join(" · ");
				return (
					<Box
						component="li"
						{...props}
						sx={{
							display: "block",
							width: "100%",
							boxSizing: "border-box",
							listStyle: "none",
							borderLeft: "3px solid",
							borderLeftColor: state.selected ? "primary.main" : "transparent",
							bgcolor: state.selected ? "action.selected" : "transparent",
							transition:
								"background-color 0.15s ease, border-color 0.15s ease",
						}}
					>
						<Box
							sx={{
								display: "flex",
								alignItems: "center",
								gap: 1.5,
								py: 1.25,
								px: 1.5,
							}}
						>
							<Box
								sx={{
									flexShrink: 0,
									width: 52,
									height: 52,
									borderRadius: 1.5,
									overflow: "hidden",
									bgcolor: "action.hover",
									border: "1px solid",
									borderColor: "divider",
									display: "flex",
									alignItems: "center",
									justifyContent: "center",
									boxShadow: (theme) =>
										theme.palette.mode === "dark"
											? "inset 0 0 0 1px rgba(255,255,255,0.06)"
											: "inset 0 1px 2px rgba(0,0,0,0.04)",
								}}
							>
								{option.imageFileName ? (
									<Box
										component="img"
										src={catalogItemImageUrl(option.id, option.imageFileName)}
										alt=""
										loading="lazy"
										sx={{
											width: "100%",
											height: "100%",
											objectFit: "contain",
											display: "block",
										}}
									/>
								) : (
									<CategoryOutlinedIcon
										sx={{ fontSize: 30, color: "action.active", opacity: 0.85 }}
									/>
								)}
							</Box>
							<Box sx={{ minWidth: 0, flex: 1 }}>
								<Typography
									variant="body2"
									sx={{
										fontWeight: 700,
										lineHeight: 1.35,
										letterSpacing: 0.01,
										color: state.selected ? "primary.main" : "text.primary",
									}}
									noWrap
									title={option.name}
								>
									{option.name}
								</Typography>
								{meta ? (
									<Typography
										variant="caption"
										sx={{
											display: "block",
											mt: 0.35,
											color: "text.secondary",
											fontFamily: codes
												? "ui-monospace, SFMono-Regular, Menlo, monospace"
												: "inherit",
											fontSize: codes ? "0.72rem" : undefined,
											letterSpacing: codes ? 0.02 : undefined,
										}}
										noWrap
										title={meta}
									>
										{meta}
									</Typography>
								) : null}
								{attrs ? (
									<Typography
										variant="caption"
										sx={{
											display: "block",
											mt: 0.35,
											color: "text.disabled",
											lineHeight: 1.35,
										}}
										noWrap
										title={attrs}
									>
										{attrs}
									</Typography>
								) : null}
							</Box>
						</Box>
					</Box>
				);
			}}
			renderInput={(params) => (
				<TextField
					{...params}
					label={label}
					required={required}
					error={error}
					helperText={helperText}
					slotProps={{
						...params.slotProps,
						input: {
							...params.slotProps.input,
							startAdornment: (
								<>
									{selected ? (
										<Box
											sx={{
												display: "flex",
												alignItems: "center",
												mr: 1,
												ml: -0.25,
												flexShrink: 0,
											}}
										>
											{selected.imageFileName ? (
												<Box
													component="img"
													src={catalogItemImageUrl(
														selected.id,
														selected.imageFileName,
													)}
													alt=""
													sx={{
														width: 30,
														height: 30,
														borderRadius: 1,
														objectFit: "contain",
														border: "1px solid",
														borderColor: "divider",
														bgcolor: "action.hover",
														display: "block",
													}}
												/>
											) : (
												<Box
													sx={{
														width: 30,
														height: 30,
														borderRadius: 1,
														border: "1px solid",
														borderColor: "divider",
														bgcolor: "action.hover",
														display: "flex",
														alignItems: "center",
														justifyContent: "center",
													}}
												>
													<CategoryOutlinedIcon
														sx={{ fontSize: 18, color: "action.active" }}
													/>
												</Box>
											)}
										</Box>
									) : null}
									{params.slotProps.input.startAdornment}
								</>
							),
							endAdornment: (
								<>
									{loading ? (
										<CircularProgress color="inherit" size={20} />
									) : null}
									{params.slotProps.input.endAdornment}
								</>
							),
						},
					}}
				/>
			)}
		/>
	);
}
