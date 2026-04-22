import CalculateOutlinedIcon from "@mui/icons-material/CalculateOutlined";
import CategoryOutlinedIcon from "@mui/icons-material/CategoryOutlined";
import Inventory2OutlinedIcon from "@mui/icons-material/Inventory2Outlined";
import PlaylistRemoveOutlinedIcon from "@mui/icons-material/PlaylistRemoveOutlined";
import { Alert, Box, LinearProgress, Paper, Typography } from "@mui/material";
import type { ReactNode } from "react";
import type { CatalogItemDto, InventoryItemDto } from "../domain/barcodile";

const shellSx = {
	maxWidth: 1200,
	mx: "auto",
} as const;

function formatQty(n: number): string {
	if (Number.isInteger(n)) {
		return String(n);
	}
	return n.toLocaleString(undefined, { maximumFractionDigits: 4 });
}

type StatCardProps = {
	label: string;
	value: string;
	hint?: string;
	icon: ReactNode;
	iconBg: string;
};

function StatCard({ label, value, hint, icon, iconBg }: StatCardProps) {
	return (
		<Paper
			elevation={0}
			sx={{
				p: 2.25,
				borderRadius: 2,
				border: "1px solid",
				borderColor: "divider",
				height: "100%",
				display: "flex",
				gap: 2,
				alignItems: "flex-start",
			}}
		>
			<Box
				sx={{
					width: 44,
					height: 44,
					borderRadius: 1.5,
					display: "grid",
					placeItems: "center",
					flexShrink: 0,
					bgcolor: iconBg,
					color: "common.white",
				}}
			>
				{icon}
			</Box>
			<Box sx={{ minWidth: 0 }}>
				<Typography
					variant="caption"
					color="text.secondary"
					sx={{ fontWeight: 600 }}
				>
					{label}
				</Typography>
				<Typography
					variant="h5"
					sx={{ fontWeight: 800, lineHeight: 1.15, mt: 0.25 }}
				>
					{value}
				</Typography>
				{hint ? (
					<Typography
						variant="caption"
						color="text.secondary"
						sx={{ display: "block", mt: 0.5 }}
					>
						{hint}
					</Typography>
				) : null}
			</Box>
		</Paper>
	);
}

export type DashboardPageProps = {
	catalogItems: CatalogItemDto[];
	inventoryItems: InventoryItemDto[];
	error: string | null;
	loading: boolean;
};

export function DashboardPage({
	catalogItems,
	inventoryItems,
	error,
	loading,
}: DashboardPageProps) {
	const typeIdsWithStock = new Set(inventoryItems.map((s) => s.catalogItem.id));
	const typesWithoutStock = catalogItems.filter(
		(t) => !typeIdsWithStock.has(t.id),
	);

	const physicalUnits = inventoryItems.length;

	return (
		<Box sx={shellSx}>
			<Paper
				elevation={0}
				sx={{
					p: { xs: 2.5, sm: 3.5 },
					borderRadius: 2,
					border: "1px solid",
					borderColor: "divider",
					bgcolor: "background.paper",
				}}
			>
				<Typography variant="h5" sx={{ fontWeight: 700 }} gutterBottom>
					Dashboard
				</Typography>

				{loading ? <LinearProgress sx={{ mb: 2, borderRadius: 1 }} /> : null}

				{error ? (
					<Alert severity="error" sx={{ mb: 2 }}>
						{error}
					</Alert>
				) : null}

				<Box
					sx={{
						display: "grid",
						gap: 2,
						gridTemplateColumns: {
							xs: "1fr",
							sm: "repeat(2, 1fr)",
							md: "repeat(4, 1fr)",
						},
					}}
				>
					<StatCard
						label="Catalog items"
						value={String(catalogItems.length)}
						hint="Distinct products you can stock"
						icon={<CategoryOutlinedIcon />}
						iconBg="primary.main"
					/>
					<StatCard
						label="Inventory rows"
						value={String(inventoryItems.length)}
						hint="Lines in stock"
						icon={<Inventory2OutlinedIcon />}
						iconBg="secondary.main"
					/>
					<StatCard
						label="Not on hand yet"
						value={String(typesWithoutStock.length)}
						hint="Catalog items with no inventory row"
						icon={<PlaylistRemoveOutlinedIcon />}
						iconBg={
							typesWithoutStock.length > 0 ? "warning.main" : "success.main"
						}
					/>
					<StatCard
						label="Physical units"
						value={formatQty(physicalUnits)}
						hint="One row per physical item in stock"
						icon={<CalculateOutlinedIcon />}
						iconBg="info.main"
					/>
				</Box>
			</Paper>
		</Box>
	);
}
