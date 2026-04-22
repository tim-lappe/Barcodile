import {
	Alert,
	Box,
	Paper,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	Tooltip,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { fetchActivity } from "../api/barcodileClient";
import type { PersistedDomainEventItemDto } from "../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

function shortEventClassName(eventClass: string): string {
	const parts = eventClass.split("\\");
	return parts[parts.length - 1] ?? eventClass;
}

function formatDataJson(data: unknown): string {
	try {
		return JSON.stringify(data, null, 2);
	} catch {
		return String(data);
	}
}

function formatDate(iso: string): string {
	try {
		return new Date(iso).toLocaleString(undefined, {
			dateStyle: "medium",
			timeStyle: "medium",
		});
	} catch {
		return iso;
	}
}

export function ActivityPage() {
	const [rows, setRows] = useState<PersistedDomainEventItemDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);

	const load = useCallback(async () => {
		setError(null);
		setLoading(true);
		try {
			const list = await fetchActivity();
			setRows(list.items);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	return (
		<Paper elevation={0} sx={paperSx}>
			<Box
				sx={{
					display: "flex",
					flexDirection: { xs: "column", sm: "row" },
					alignItems: { xs: "flex-start", sm: "center" },
					justifyContent: "space-between",
					gap: 1.5,
					mb: 2.5,
				}}
			>
				<Box>
					<Typography variant="h5" component="h1" sx={{ fontWeight: 700, mb: 0.5 }}>
						Activity
					</Typography>
					<Typography variant="body2" color="text.secondary">
						Last 200 domain events, newest first.
					</Typography>
				</Box>
			</Box>
			{error ? (
				<Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
					{error}
				</Alert>
			) : null}
			<TableContainer
				sx={{
					border: "1px solid",
					borderColor: "divider",
					borderRadius: 1,
					maxHeight: { xs: "60vh", md: "min(70vh, 640px)" },
					overflow: "auto",
				}}
			>
				<Table size="small" stickyHeader>
					<TableHead>
						<TableRow>
							<TableCell sx={{ fontWeight: 700, minWidth: 200 }}>When</TableCell>
							<TableCell sx={{ fontWeight: 700, minWidth: 160 }}>Event</TableCell>
							<TableCell sx={{ fontWeight: 700 }}>Payload</TableCell>
						</TableRow>
					</TableHead>
					<TableBody>
						{loading ? (
							<TableRow>
								<TableCell colSpan={3}>
									<Typography variant="body2" color="text.secondary">
										Loading…
									</Typography>
								</TableCell>
							</TableRow>
						) : rows.length === 0 ? (
							<TableRow>
								<TableCell colSpan={3}>
									<Typography variant="body2" color="text.secondary">
										No events recorded yet.
									</Typography>
								</TableCell>
							</TableRow>
						) : (
							rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell
										sx={{
											whiteSpace: "nowrap",
											verticalAlign: "top",
											color: "text.secondary",
										}}
									>
										{formatDate(row.createdAt)}
									</TableCell>
									<TableCell sx={{ verticalAlign: "top" }}>
										<Tooltip title={row.eventClass} placement="top" arrow>
											<Typography
												variant="body2"
												sx={{ fontWeight: 600, cursor: "default" }}
											>
												{shortEventClassName(row.eventClass)}
											</Typography>
										</Tooltip>
									</TableCell>
									<TableCell
										sx={{
											fontFamily: "ui-monospace, Menlo, Monaco, Consolas, monospace",
											fontSize: 12,
											verticalAlign: "top",
											wordBreak: "break-word",
										}}
									>
										<Box
											component="pre"
											sx={{ m: 0, p: 0, whiteSpace: "pre-wrap" }}
										>
											{formatDataJson(row.data)}
										</Box>
									</TableCell>
								</TableRow>
							))
						)}
					</TableBody>
				</Table>
			</TableContainer>
		</Paper>
	);
}
