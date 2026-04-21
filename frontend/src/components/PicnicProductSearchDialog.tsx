import SearchIcon from "@mui/icons-material/Search";
import {
	Alert,
	Box,
	Button,
	CircularProgress,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	Link,
	List,
	ListItemButton,
	ListItemText,
	TextField,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink } from "react-router-dom";
import { fetchPicnicCatalogSearch } from "../api/barcodileClient";
import type { PicnicCatalogSearchHitDto } from "../domain/barcodile";
import { usePicnicConnection } from "../picnic/usePicnicConnection";

function formatDisplayPrice(cents: number | null | undefined): string {
	if (cents === null || cents === undefined) {
		return "";
	}
	return new Intl.NumberFormat(undefined, {
		style: "currency",
		currency: "EUR",
	}).format(cents / 100);
}

export type PicnicProductSearchDialogProps = {
	open: boolean;
	onClose: () => void;
	onProductChosen: (productId: string) => void;
};

export function PicnicProductSearchDialog({
	open,
	onClose,
	onProductChosen,
}: PicnicProductSearchDialogProps) {
	const { picnicConnected, picnicStatusLoading } = usePicnicConnection();
	const [query, setQuery] = useState("");
	const [debouncedQuery, setDebouncedQuery] = useState("");
	const [hits, setHits] = useState<PicnicCatalogSearchHitDto[]>([]);
	const [loading, setLoading] = useState(false);
	const [error, setError] = useState<string | null>(null);

	useEffect(() => {
		const t = window.setTimeout(() => setDebouncedQuery(query.trim()), 380);
		return () => window.clearTimeout(t);
	}, [query]);

	const runSearch = useCallback(async (q: string) => {
		if (q.length < 2) {
			setHits([]);
			setError(null);
			setLoading(false);
			return;
		}
		setLoading(true);
		setError(null);
		try {
			const rows = await fetchPicnicCatalogSearch(q);
			setHits(rows);
		} catch (e) {
			setHits([]);
			setError(e instanceof Error ? e.message : "Search failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		if (!open || !picnicConnected || picnicStatusLoading) {
			return;
		}
		void runSearch(debouncedQuery);
	}, [open, picnicConnected, picnicStatusLoading, debouncedQuery, runSearch]);

	useEffect(() => {
		if (!open) {
			setQuery("");
			setDebouncedQuery("");
			setHits([]);
			setError(null);
			setLoading(false);
		}
	}, [open]);

	return (
		<Dialog
			open={open}
			onClose={onClose}
			fullWidth
			maxWidth="md"
			aria-labelledby="picnic-product-search-title"
			slotProps={{
				paper: {
					sx: {
						borderRadius: 2,
						bgcolor: "background.paper",
						color: "text.primary",
						colorScheme: "light",
					},
				},
			}}
		>
			<DialogTitle
				id="picnic-product-search-title"
				sx={{ fontWeight: 700, color: "text.primary" }}
			>
				Search Picnic products
			</DialogTitle>
			<DialogContent sx={{ color: "text.primary" }}>
				<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
					Pick a product from your Picnic catalog. You will continue on the
					manual catalog item form with the Picnic link prefilled.
				</Typography>
				{picnicStatusLoading ? (
					<Box sx={{ display: "flex", justifyContent: "center", py: 3 }}>
						<CircularProgress size={32} />
					</Box>
				) : !picnicConnected ? (
					<Alert severity="info" sx={{ mb: 2 }}>
						<Typography variant="body2" component="span">
							Connect Picnic under{" "}
							<Link component={RouterLink} to="/settings/picnic">
								Settings → Picnic
							</Link>{" "}
							to search the catalog.
						</Typography>
					</Alert>
				) : (
					<>
						<TextField
							fullWidth
							autoFocus
							label="Search"
							placeholder="At least two characters"
							value={query}
							onChange={(e) => setQuery(e.target.value)}
							slotProps={{
								input: {
									endAdornment: loading ? (
										<CircularProgress color="inherit" size={20} />
									) : (
										<SearchIcon color="action" />
									),
								},
							}}
							sx={{ mb: 2 }}
						/>
						{error ? (
							<Alert
								severity="error"
								sx={{ mb: 2 }}
								onClose={() => setError(null)}
							>
								{error}
							</Alert>
						) : null}
						{debouncedQuery.length >= 2 &&
						!loading &&
						hits.length === 0 &&
						!error ? (
							<Typography variant="body2" color="text.secondary">
								No products found.
							</Typography>
						) : null}
						<List
							dense
							disablePadding
							sx={{ maxHeight: 360, overflow: "auto" }}
						>
							{hits.map((h) => {
								const price = formatDisplayPrice(h.displayPrice);
								const bits = [h.id, h.unitQuantity, price].filter(Boolean);
								return (
									<ListItemButton
										key={h.id}
										onClick={() => {
											onProductChosen(h.id);
											onClose();
										}}
										sx={{ borderRadius: 1, mb: 0.5 }}
									>
										<ListItemText
											sx={{
												"& .MuiListItemText-primary": {
													fontWeight: 600,
													color: "text.primary",
												},
												"& .MuiListItemText-secondary": {
													color: "text.secondary",
													fontFamily: "ui-monospace, monospace",
													fontSize: 12,
												},
											}}
											primary={h.name || h.id}
											secondary={bits.join(" · ")}
										/>
									</ListItemButton>
								);
							})}
						</List>
					</>
				)}
			</DialogContent>
			<DialogActions sx={{ px: 3, pb: 2, color: "text.primary" }}>
				<Button onClick={onClose} color="primary" variant="text">
					Cancel
				</Button>
			</DialogActions>
		</Dialog>
	);
}
