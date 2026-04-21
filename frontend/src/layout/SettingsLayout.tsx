import ShoppingBagOutlinedIcon from "@mui/icons-material/ShoppingBagOutlined";
import {
	Box,
	Drawer,
	List,
	ListItemButton,
	ListItemIcon,
	ListItemText,
	Toolbar,
	Typography,
} from "@mui/material";
import { NavLink, Outlet } from "react-router-dom";

const DRAWER_WIDTH = 260;

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
					<ListItemButton
						component={NavLink}
						to="/settings/picnic"
						sx={{
							borderRadius: 1.5,
							"&.active": { bgcolor: "action.selected", fontWeight: 600 },
						}}
					>
						<ListItemIcon sx={{ minWidth: 40 }}>
							<ShoppingBagOutlinedIcon fontSize="small" />
						</ListItemIcon>
						<ListItemText
							primary="Picnic"
							sx={{ "& .MuiListItemText-primary": { fontSize: 14 } }}
						/>
					</ListItemButton>
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
