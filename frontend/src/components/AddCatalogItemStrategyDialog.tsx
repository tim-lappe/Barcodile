import type { SvgIconComponent } from "@mui/icons-material";
import DrawOutlinedIcon from "@mui/icons-material/DrawOutlined";
import QrCode2OutlinedIcon from "@mui/icons-material/QrCode2Outlined";
import ShoppingBagOutlinedIcon from "@mui/icons-material/ShoppingBagOutlined";
import {
	Box,
	Button,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	Typography,
} from "@mui/material";
import { useNavigate } from "react-router-dom";
import {
	CATALOG_ITEM_CREATION_SOURCES,
	type CatalogItemCreationSourceId,
	catalogItemNewPath,
} from "../catalog/catalogItemCreationSources";

const TILE_ICONS: Record<CatalogItemCreationSourceId, SvgIconComponent> = {
	manual: DrawOutlinedIcon,
	picnic: ShoppingBagOutlinedIcon,
	barcode: QrCode2OutlinedIcon,
};

export type AddCatalogItemStrategyDialogProps = {
	open: boolean;
	onClose: () => void;
	onChooseSource: (id: CatalogItemCreationSourceId) => void;
	onChooseBarcodeCreate?: () => void;
};

export function AddCatalogItemStrategyDialog({
	open,
	onClose,
	onChooseSource,
	onChooseBarcodeCreate,
}: AddCatalogItemStrategyDialogProps) {
	const navigate = useNavigate();

	return (
		<Dialog
			open={open}
			onClose={onClose}
			fullWidth
			maxWidth="lg"
			aria-labelledby="add-catalog-item-strategy-title"
			slotProps={{
				paper: {
					sx: {
						borderRadius: 2,
						minHeight: { xs: "auto", sm: 360 },
						bgcolor: "background.paper",
						color: "text.primary",
						colorScheme: "light",
					},
				},
			}}
		>
			<DialogTitle
				id="add-catalog-item-strategy-title"
				sx={{ fontWeight: 700, pb: 1, color: "text.primary" }}
			>
				Add catalog item
			</DialogTitle>
			<DialogContent sx={{ pt: 0, color: "text.primary" }}>
				<Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
					Choose how you want to build this template. You can always edit
					details before saving.
				</Typography>
				<Box
					sx={{
						display: "grid",
						gridTemplateColumns: { xs: "1fr", md: "repeat(3, 1fr)" },
						gap: 2,
					}}
				>
					{CATALOG_ITEM_CREATION_SOURCES.map((s) => {
						const Icon = TILE_ICONS[s.id];
						return (
							<Box
								key={s.id}
								component="button"
								type="button"
								onClick={() => {
									onClose();
									if (s.id === "picnic") {
										onChooseSource("picnic");
										return;
									}
									if (s.id === "barcode") {
										onChooseBarcodeCreate?.();
										return;
									}
									navigate(catalogItemNewPath("manual"));
								}}
								sx={{
									m: 0,
									p: 0,
									textAlign: "left",
									cursor: "pointer",
									border: "1px solid",
									borderColor: "divider",
									borderRadius: 2,
									bgcolor: "background.paper",
									color: "text.primary",
									font: "inherit",
									fontFamily: "inherit",
									transition:
										"border-color 0.2s, box-shadow 0.2s, background-color 0.2s",
									"&:hover": {
										borderColor: "primary.main",
										bgcolor: "action.hover",
										boxShadow: 2,
									},
								}}
							>
								<Box
									sx={{
										p: 2.5,
										display: "flex",
										flexDirection: "column",
										gap: 1.5,
										color: "text.primary",
									}}
								>
									<Box
										sx={{
											width: 48,
											height: 48,
											borderRadius: 1.5,
											bgcolor: "primary.main",
											color: "primary.contrastText",
											display: "flex",
											alignItems: "center",
											justifyContent: "center",
										}}
									>
										<Icon sx={{ fontSize: 28 }} />
									</Box>
									<Typography
										variant="h6"
										sx={{ fontWeight: 700, color: "text.primary" }}
									>
										{s.tileTitle}
									</Typography>
									<Typography variant="body2" color="text.secondary">
										{s.tileDescription}
									</Typography>
								</Box>
							</Box>
						);
					})}
				</Box>
			</DialogContent>
			<DialogActions sx={{ px: 3, pb: 2, color: "text.primary" }}>
				<Button onClick={onClose} color="primary" variant="text">
					Cancel
				</Button>
			</DialogActions>
		</Dialog>
	);
}
