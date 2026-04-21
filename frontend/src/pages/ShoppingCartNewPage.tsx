import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import {
	Alert,
	Box,
	Breadcrumbs,
	Button,
	Link,
	Paper,
	TextField,
	Typography,
} from "@mui/material";
import { useState } from "react";
import { Link as RouterLink, useNavigate } from "react-router-dom";
import { createShoppingCart } from "../api/barcodileClient";

const shellSx = {
	maxWidth: 640,
	mx: "auto",
} as const;

const sectionPaperSx = {
	p: { xs: 2, sm: 2.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
} as const;

export function ShoppingCartNewPage() {
	const navigate = useNavigate();
	const [name, setName] = useState("");
	const [saving, setSaving] = useState(false);
	const [error, setError] = useState<string | null>(null);

	async function submit() {
		setError(null);
		setSaving(true);
		try {
			const trimmed = name.trim();
			const created = await createShoppingCart({
				name: trimmed === "" ? null : trimmed,
			});
			navigate(`/carts/${created.id}`);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
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
				<Typography color="text.primary">New</Typography>
			</Breadcrumbs>
			<Paper elevation={0} sx={sectionPaperSx}>
				<Typography variant="h5" sx={{ fontWeight: 700, mb: 2 }}>
					New cart
				</Typography>
				{error ? (
					<Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
						{error}
					</Alert>
				) : null}
				<Box
					component="form"
					sx={{ display: "flex", flexDirection: "column", gap: 2 }}
					onSubmit={(e) => e.preventDefault()}
				>
					<TextField
						label="Name (optional)"
						value={name}
						onChange={(e) => setName(e.target.value)}
						fullWidth
						autoComplete="off"
					/>
					<Box sx={{ display: "flex", flexWrap: "wrap", gap: 1.5, mt: 1 }}>
						<Button
							type="button"
							variant="contained"
							onClick={() => void submit()}
							disabled={saving}
						>
							Create
						</Button>
						<Button
							component={RouterLink}
							to="/carts"
							startIcon={<ArrowBackIcon />}
							disabled={saving}
						>
							Back
						</Button>
					</Box>
				</Box>
			</Paper>
		</Box>
	);
}
