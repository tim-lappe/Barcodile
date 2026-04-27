import PlaceOutlinedIcon from "@mui/icons-material/PlaceOutlined";
import PrintOutlinedIcon from "@mui/icons-material/PrintOutlined";
import QrCodeScannerOutlinedIcon from "@mui/icons-material/QrCodeScannerOutlined";
import ShoppingBagOutlinedIcon from "@mui/icons-material/ShoppingBagOutlined";
import {
	Box,
	Divider,
	Drawer,
	List,
	ListItemButton,
	ListItemIcon,
	ListItemText,
	ListSubheader,
	Toolbar,
	Typography,
} from "@mui/material";
import type { ReactNode } from "react";
import { NavLink, Outlet } from "react-router-dom";

const DRAWER_WIDTH = 260;

type SettingsMenuItem = {
	label: string;
	to: string;
	icon: ReactNode;
};

const SETTINGS_MENU_SECTIONS: { title: string; items: SettingsMenuItem[] }[] = [
	{
		title: "Hardware",
		items: [
			{
				label: "Scanner",
				to: "/settings/scanner",
				icon: <QrCodeScannerOutlinedIcon fontSize="small" />,
			},
			{
				label: "Printers",
				to: "/settings/printers",
				icon: <PrintOutlinedIcon fontSize="small" />,
			},
		],
	},
	{
		title: "Inventory",
		items: [
			{
				label: "Locations",
				to: "/settings/locations",
				icon: <PlaceOutlinedIcon fontSize="small" />,
			},
		],
	},
	{
		title: "Integrations",
		items: [
			{
				label: "Picnic",
				to: "/settings/picnic",
				icon: <ShoppingBagOutlinedIcon fontSize="small" />,
			},
		],
	},
];

export function SettingsLayout() {
	return (
		<Box
			sx={{
				display: "flex",
				alignItems: "stretch",
				gap: { xs: 2, sm: 3 },
				width: "100%",
				minHeight: "calc(100vh - 120px)",
			}}
		>
			<Drawer
				variant="permanent"
				sx={{
					width: DRAWER_WIDTH,
					flexShrink: 0,
					"& .MuiDrawer-paper": {
						width: DRAWER_WIDTH,
						boxSizing: "border-box",
						position: "relative",
						border: "1px solid",
						borderColor: "divider",
						borderRadius: 2,
						bgcolor: "background.paper",
						height: "auto",
						alignSelf: "stretch",
					},
				}}
			>
				<Toolbar variant="dense" sx={{ minHeight: 48, px: 2, py: 1.5 }}>
					<Typography
						variant="subtitle2"
						sx={{ fontWeight: 700, letterSpacing: "0.02em" }}
					>
						Settings
					</Typography>
				</Toolbar>
				<List dense sx={{ px: 1, pb: 1 }}>
					{SETTINGS_MENU_SECTIONS.map((section, sectionIndex) => (
						<Box key={section.title} component="li" sx={{ listStyle: "none" }}>
							{sectionIndex > 0 ? <Divider sx={{ my: 1 }} /> : null}
							<ListSubheader
								disableSticky
								component="div"
								sx={{
									bgcolor: "transparent",
									color: "text.secondary",
									fontSize: 11,
									fontWeight: 700,
									letterSpacing: "0.08em",
									lineHeight: 2.4,
									px: 1.5,
									textTransform: "uppercase",
								}}
							>
								{section.title}
							</ListSubheader>
							{section.items.map((item) => (
								<ListItemButton
									key={item.to}
									component={NavLink}
									to={item.to}
									sx={{
										borderRadius: 1.5,
										"&.active": { bgcolor: "action.selected", fontWeight: 600 },
									}}
								>
									<ListItemIcon sx={{ minWidth: 40 }}>{item.icon}</ListItemIcon>
									<ListItemText
										primary={item.label}
										sx={{ "& .MuiListItemText-primary": { fontSize: 14 } }}
									/>
								</ListItemButton>
							))}
						</Box>
					))}
				</List>
			</Drawer>
			<Box
				component="section"
				sx={{
					flex: 1,
					minWidth: 0,
					border: "1px solid",
					borderColor: "divider",
					borderRadius: 2,
					bgcolor: "background.paper",
					p: { xs: 2, sm: 3 },
				}}
			>
				<Outlet />
			</Box>
		</Box>
	);
}
