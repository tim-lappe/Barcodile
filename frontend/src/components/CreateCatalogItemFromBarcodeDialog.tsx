import {
	Alert,
	Button,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	TextField,
	Typography,
} from "@mui/material";
import { useEffect, useState } from "react";
import { postCreateCatalogItemFromBarcode } from "../api/barcodileClient";
import type { CatalogItemId } from "../domain/barcodile";

export type CreateCatalogItemFromBarcodeDialogProps = {
	open: boolean;
	onClose: () => void;
	onCreated: (id: CatalogItemId) => void;
};

export function CreateCatalogItemFromBarcodeDialog({
	open,
	onClose,
	onCreated,
}: CreateCatalogItemFromBarcodeDialogProps) {
	const [code, setCode] = useState("");
	const [type, setType] = useState("EAN");
	const [error, setError] = useState<string | null>(null);
	const [busy, setBusy] = useState(false);

	useEffect(() => {
		if (open) {
			setCode("");
			setType("EAN");
			setError(null);
			setBusy(false);
		}
	}, [open]);

	async function submit() {
		setError(null);
		const trimmed = code.trim();
		if (trimmed === "") {
			setError("Enter a barcode.");
			return;
		}
		setBusy(true);
		try {
			const item = await postCreateCatalogItemFromBarcode({
				code: trimmed,
				type: type.trim() || "EAN",
			});
			onClose();
			onCreated(item.id);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Create failed");
		} finally {
			setBusy(false);
		}
	}

	return (
		<Dialog
			open={open}
			onClose={busy ? undefined : onClose}
			fullWidth
			maxWidth="sm"
			slotProps={{ paper: { sx: { borderRadius: 2 } } }}
			aria-labelledby="create-from-barcode-title"
		>
			<DialogTitle id="create-from-barcode-title">
				Create from barcode
			</DialogTitle>
			<DialogContent>
				<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
					Uses your first enabled OpenAI LLM profile with web search, then saves
					a new catalog item. Open the editor afterwards if you need to adjust
					details.
				</Typography>
				{error ? (
					<Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
						{error}
					</Alert>
				) : null}
				<TextField
					label="Barcode"
					value={code}
					onChange={(e) => setCode(e.target.value)}
					fullWidth
					autoFocus
					sx={{ mb: 2 }}
					disabled={busy}
					slotProps={{
						htmlInput: { sx: { fontFamily: "ui-monospace, monospace" } },
					}}
				/>
				<TextField
					label="Symbology"
					value={type}
					onChange={(e) => setType(e.target.value)}
					fullWidth
					disabled={busy}
					helperText="For example EAN or UPC."
				/>
			</DialogContent>
			<DialogActions sx={{ px: 3, pb: 2 }}>
				<Button onClick={onClose} disabled={busy}>
					Cancel
				</Button>
				<Button
					variant="contained"
					onClick={() => void submit()}
					disabled={busy}
				>
					{busy ? "Creating…" : "Create"}
				</Button>
			</DialogActions>
		</Dialog>
	);
}
