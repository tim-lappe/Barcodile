import LoginIcon from "@mui/icons-material/Login";
import LogoutIcon from "@mui/icons-material/Logout";
import PersonOutlinedIcon from "@mui/icons-material/PersonOutlined";
import HistoryOutlinedIcon from "@mui/icons-material/HistoryOutlined";
import SettingsOutlinedIcon from "@mui/icons-material/SettingsOutlined";
import {
	AppBar,
	Avatar,
	Box,
	Button,
	Divider,
	IconButton,
	ListItemIcon,
	ListItemText,
	Menu,
	MenuItem,
	Toolbar,
	Typography,
} from "@mui/material";
import { type ReactNode, useState } from "react";
import { Link as RouterLink, useLocation, useNavigate } from "react-router-dom";

export type AdminNavKey =
	| "dashboard"
	| "catalogItems"
	| "locations"
	| "stock"
	| "carts"
	| "devices"
	| "printers";

const NAV_ITEMS: { key: AdminNavKey; label: string }[] = [
	{ key: "dashboard", label: "Dashboard" },
	{ key: "catalogItems", label: "Catalog items" },
	{ key: "locations", label: "Locations" },
	{ key: "stock", label: "Inventory" },
	{ key: "carts", label: "Carts" },
	{ key: "devices", label: "Devices" },
	{ key: "printers", label: "Printers" },
];

function navKeyFromPath(pathname: string): AdminNavKey {
	if (pathname.startsWith("/catalog-items")) {
		return "catalogItems";
	}
	if (pathname.startsWith("/locations")) {
		return "locations";
	}
	if (pathname.startsWith("/inventory")) {
		return "stock";
	}
	if (pathname.startsWith("/carts")) {
		return "carts";
	}
	if (pathname.startsWith("/devices")) {
		return "devices";
	}
	if (pathname.startsWith("/printers")) {
		return "printers";
	}
	return "dashboard";
}

type AdminLayoutProps = {
	children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
	const navigate = useNavigate();
	const { pathname } = useLocation();
	const activeNav = navKeyFromPath(pathname);
	const activityLinkActive = pathname === "/activity" || pathname.startsWith("/activity/");

	function goNav(key: AdminNavKey) {
		if (key === "dashboard") {
			navigate("/");
		} else if (key === "catalogItems") {
			navigate("/catalog-items");
		} else if (key === "locations") {
			navigate("/locations");
		} else if (key === "carts") {
			navigate("/carts");
		} else if (key === "devices") {
			navigate("/devices");
		} else if (key === "printers") {
			navigate("/printers");
		} else {
			navigate("/inventory");
		}
	}

	const [userAnchor, setUserAnchor] = useState<null | HTMLElement>(null);
	const userOpen = Boolean(userAnchor);

	return (
		<Box
			sx={{
				display: "flex",
				flexDirection: "column",
				minHeight: "100vh",
				width: "100%",
				bgcolor: "background.default",
			}}
		>
			<AppBar
				position="sticky"
				elevation={0}
				sx={{
					bgcolor: "grey.900",
					backgroundImage:
						"linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0) 100%)",
					borderBottom: "1px solid",
					borderColor: "rgba(255,255,255,0.08)",
				}}
			>
				<Toolbar
					disableGutters
					sx={{
						px: { xs: 2, sm: 3 },
						minHeight: { xs: 56, sm: 64 },
						gap: 2,
					}}
				>
					<Box
						sx={{
							display: "flex",
							alignItems: "center",
							gap: 1.25,
							mr: { xs: 0, md: 2 },
						}}
					>
						<Box
							component="img"
							src="/logo.png"
							alt="Barcodile"
							sx={{
								width: 40,
								height: 40,
								borderRadius: 1.5,
								display: "block",
								objectFit: "contain",
							}}
						/>
						<Box sx={{ display: { xs: "none", sm: "block" } }}>
							<Typography
								variant="subtitle1"
								sx={{
									fontWeight: 700,
									letterSpacing: "-0.02em",
									lineHeight: 1.2,
									color: "common.white",
								}}
							>
								Barcodile
							</Typography>
						</Box>
					</Box>

					<Box
						sx={{
							display: { xs: "none", md: "flex" },
							alignItems: "center",
							gap: 0.5,
						}}
					>
						{NAV_ITEMS.map((item) => (
							<Button
								key={item.key}
								onClick={() => goNav(item.key)}
								color="inherit"
								sx={{
									px: 1.75,
									py: 0.75,
									borderRadius: 2,
									fontWeight: 600,
									fontSize: 14,
									textTransform: "none",
									color:
										activeNav === item.key
											? "common.white"
											: "rgba(255,255,255,0.65)",
									bgcolor:
										activeNav === item.key
											? "rgba(255,255,255,0.12)"
											: "transparent",
									"&:hover": {
										bgcolor:
											activeNav === item.key
												? "rgba(255,255,255,0.16)"
												: "rgba(255,255,255,0.08)",
									},
								}}
							>
								{item.label}
							</Button>
						))}
					</Box>

					<Box sx={{ flexGrow: 1 }} />

					<Box sx={{ display: "flex", alignItems: "center", gap: 0.5 }}>
						<IconButton
							color="inherit"
							size="medium"
							component={RouterLink}
							to="/activity"
							sx={{
								color: activityLinkActive
									? "common.white"
									: "rgba(255,255,255,0.85)",
								bgcolor: activityLinkActive
									? "rgba(255,255,255,0.12)"
									: "transparent",
								"&:hover": {
									bgcolor: activityLinkActive
										? "rgba(255,255,255,0.16)"
										: "rgba(255,255,255,0.08)",
								},
							}}
							aria-label="Activity"
						>
							<HistoryOutlinedIcon />
						</IconButton>
						<IconButton
							color="inherit"
							size="medium"
							component={RouterLink}
							to="/settings"
							sx={{ color: "rgba(255,255,255,0.85)" }}
							aria-label="Settings"
						>
							<SettingsOutlinedIcon />
						</IconButton>
						<Divider
							orientation="vertical"
							flexItem
							sx={{
								mx: 0.5,
								borderColor: "rgba(255,255,255,0.12)",
								alignSelf: "center",
								height: 28,
							}}
						/>
						<Button
							color="inherit"
							onClick={(e) => setUserAnchor(e.currentTarget)}
							startIcon={
								<Avatar
									sx={{
										width: 32,
										height: 32,
										bgcolor: "primary.main",
										fontSize: 14,
										fontWeight: 700,
									}}
								>
									AD
								</Avatar>
							}
							sx={{
								textTransform: "none",
								color: "common.white",
								pl: 0.5,
								pr: 1,
								borderRadius: 999,
								"& .MuiButton-startIcon": { mr: 1 },
							}}
						>
							<Box
								sx={{
									textAlign: "left",
									display: { xs: "none", sm: "flex" },
									flexDirection: "column",
									gap: 0.25,
									py: 0.25,
								}}
							>
								<Typography
									variant="body2"
									sx={{ fontWeight: 600, lineHeight: 1.15, m: 0 }}
								>
									Admin
								</Typography>
								<Typography
									variant="caption"
									sx={{
										color: "rgba(255,255,255,0.55)",
										lineHeight: 1.15,
										m: 0,
									}}
								>
									Signed in
								</Typography>
							</Box>
						</Button>
						<Menu
							anchorEl={userAnchor}
							open={userOpen}
							onClose={() => setUserAnchor(null)}
							anchorOrigin={{ vertical: "bottom", horizontal: "right" }}
							transformOrigin={{ vertical: "top", horizontal: "right" }}
							slotProps={{
								paper: {
									elevation: 8,
									sx: {
										mt: 1.5,
										minWidth: 220,
										borderRadius: 2,
										border: "1px solid",
										borderColor: "divider",
									},
								},
							}}
						>
							<MenuItem onClick={() => setUserAnchor(null)}>
								<ListItemIcon>
									<PersonOutlinedIcon fontSize="small" />
								</ListItemIcon>
								<ListItemText primary="Profile" secondary="Account settings" />
							</MenuItem>
							<MenuItem onClick={() => setUserAnchor(null)}>
								<ListItemIcon>
									<SettingsOutlinedIcon fontSize="small" />
								</ListItemIcon>
								<ListItemText primary="Preferences" />
							</MenuItem>
							<Divider />
							<MenuItem onClick={() => setUserAnchor(null)}>
								<ListItemIcon>
									<LoginIcon fontSize="small" />
								</ListItemIcon>
								<ListItemText primary="Switch account" />
							</MenuItem>
							<MenuItem onClick={() => setUserAnchor(null)}>
								<ListItemIcon>
									<LogoutIcon fontSize="small" />
								</ListItemIcon>
								<ListItemText primary="Sign out" />
							</MenuItem>
						</Menu>
					</Box>
				</Toolbar>

				<Box
					sx={{
						display: { xs: "flex", md: "none" },
						px: 2,
						pb: 1.5,
						gap: 0.5,
						overflowX: "auto",
						WebkitOverflowScrolling: "touch",
					}}
				>
					{NAV_ITEMS.map((item) => (
						<Button
							key={item.key}
							size="small"
							onClick={() => goNav(item.key)}
							color="inherit"
							sx={{
								flexShrink: 0,
								borderRadius: 2,
								fontWeight: 600,
								textTransform: "none",
								color:
									activeNav === item.key
										? "common.white"
										: "rgba(255,255,255,0.65)",
								bgcolor:
									activeNav === item.key
										? "rgba(255,255,255,0.12)"
										: "transparent",
							}}
						>
							{item.label}
						</Button>
					))}
				</Box>
			</AppBar>

			<Box
				component="main"
				sx={{
					flex: 1,
					width: "100%",
					px: { xs: 2, sm: 3 },
					py: { xs: 2, sm: 3 },
					boxSizing: "border-box",
				}}
			>
				{children}
			</Box>
		</Box>
	);
}
