import RefreshOutlinedIcon from "@mui/icons-material/RefreshOutlined";
import {
	Alert,
	Box,
	Button,
	Chip,
	FormControl,
	InputLabel,
	MenuItem,
	Paper,
	Select,
	Stack,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	TextField,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { fetchDebugLogs } from "../../../api/barcodileClient";
import type { LogEntryDto } from "../../../domain/barcodile";

const LOG_LIMIT = 200;

const LEVEL_OPTIONS = [
	"DEBUG",
	"INFO",
	"NOTICE",
	"WARNING",
	"ERROR",
	"CRITICAL",
] as const;

type LogLevelFilter = "" | (typeof LEVEL_OPTIONS)[number];

function formatDate(value: string | null): string {
	if (!value) {
		return "";
	}
	try {
		return new Date(value).toLocaleString(undefined, {
			dateStyle: "medium",
			timeStyle: "medium",
		});
	} catch {
		return value;
	}
}

function levelChipColor(
	level: string | null,
): "default" | "info" | "warning" | "error" {
	if (
		level === "ERROR" ||
		level === "CRITICAL" ||
		level === "ALERT" ||
		level === "EMERGENCY"
	) {
		return "error";
	}
	if (level === "WARNING") {
		return "warning";
	}
	if (level === "INFO" || level === "NOTICE") {
		return "info";
	}
	return "default";
}

export function LogsPage() {
	const [rows, setRows] = useState<LogEntryDto[]>([]);
	const [source, setSource] = useState("dev.log");
	const [level, setLevel] = useState<LogLevelFilter>("");
	const [channel, setChannel] = useState("");
	const [query, setQuery] = useState("");
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);

	const load = useCallback(async () => {
		setLoading(true);
		setError(null);
		try {
			const list = await fetchDebugLogs({
				limit: LOG_LIMIT,
				level,
				channel,
				query,
			});
			setRows(list.items);
			setSource(list.source);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Failed to load logs");
		} finally {
			setLoading(false);
		}
	}, [level, channel, query]);

	useEffect(() => {
		void load();
	}, [load]);

	return (
		<Stack spacing={3}>
			<Stack
				sx={{
					flexDirection: { xs: "column", md: "row" },
					alignItems: { xs: "stretch", md: "flex-start" },
					justifyContent: "space-between",
					gap: 2,
				}}
			>
				<Box>
					<Typography variant="h5" sx={{ fontWeight: 700 }}>
						Logs
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
						Showing the newest {LOG_LIMIT} entries from {source}.
					</Typography>
				</Box>
				<Button
					type="button"
					variant="outlined"
					startIcon={<RefreshOutlinedIcon />}
					disabled={loading}
					onClick={() => void load()}
					sx={{ alignSelf: { xs: "flex-start", md: "center" } }}
				>
					Refresh
				</Button>
			</Stack>

			<Paper
				elevation={0}
				sx={{
					p: 2,
					border: "1px solid",
					borderColor: "divider",
					borderRadius: 2,
				}}
			>
				<Stack
					sx={{
						flexDirection: { xs: "column", md: "row" },
						gap: 2,
						alignItems: { xs: "stretch", md: "center" },
					}}
				>
					<TextField
						label="Search"
						value={query}
						onChange={(event) => setQuery(event.target.value)}
						placeholder="Message or context"
						size="small"
						fullWidth
					/>
					<TextField
						label="Channel"
						value={channel}
						onChange={(event) => setChannel(event.target.value)}
						placeholder="app, request, doctrine"
						size="small"
						sx={{ minWidth: 220 }}
					/>
					<FormControl size="small" sx={{ minWidth: 180 }}>
						<InputLabel id="debug-log-level-label">Level</InputLabel>
						<Select<LogLevelFilter>
							labelId="debug-log-level-label"
							label="Level"
							value={level}
							onChange={(event) =>
								setLevel(event.target.value as LogLevelFilter)
							}
						>
							<MenuItem value="">All levels</MenuItem>
							{LEVEL_OPTIONS.map((option) => (
								<MenuItem key={option} value={option}>
									{option}
								</MenuItem>
							))}
						</Select>
					</FormControl>
				</Stack>
			</Paper>

			{error ? <Alert severity="error">{error}</Alert> : null}

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
							<TableCell sx={{ fontWeight: 700, minWidth: 180 }}>
								When
							</TableCell>
							<TableCell sx={{ fontWeight: 700, minWidth: 120 }}>
								Level
							</TableCell>
							<TableCell sx={{ fontWeight: 700, minWidth: 140 }}>
								Channel
							</TableCell>
							<TableCell sx={{ fontWeight: 700 }}>Message</TableCell>
						</TableRow>
					</TableHead>
					<TableBody>
						{loading ? (
							<TableRow>
								<TableCell colSpan={4}>
									<Typography variant="body2" color="text.secondary">
										Loading…
									</Typography>
								</TableCell>
							</TableRow>
						) : rows.length === 0 ? (
							<TableRow>
								<TableCell colSpan={4}>
									<Typography variant="body2" color="text.secondary">
										No matching log entries.
									</Typography>
								</TableCell>
							</TableRow>
						) : (
							rows.map((row) => (
								<TableRow key={row.id} hover>
									<TableCell
										sx={{ whiteSpace: "nowrap", color: "text.secondary" }}
									>
										{formatDate(row.loggedAt)}
									</TableCell>
									<TableCell>
										<Chip
											size="small"
											label={row.level ?? "RAW"}
											color={levelChipColor(row.level)}
											variant="outlined"
										/>
									</TableCell>
									<TableCell sx={{ color: "text.secondary" }}>
										{row.channel ?? `Line ${row.lineNumber}`}
									</TableCell>
									<TableCell
										sx={{
											fontFamily:
												"ui-monospace, Menlo, Monaco, Consolas, monospace",
											fontSize: 12,
											wordBreak: "break-word",
										}}
									>
										<Box component="pre" sx={{ m: 0, whiteSpace: "pre-wrap" }}>
											{row.message ?? row.raw}
										</Box>
									</TableCell>
								</TableRow>
							))
						)}
					</TableBody>
				</Table>
			</TableContainer>
		</Stack>
	);
}
