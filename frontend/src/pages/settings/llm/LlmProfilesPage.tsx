import AddIcon from "@mui/icons-material/Add";
import DeleteOutlinedIcon from "@mui/icons-material/DeleteOutlined";
import PsychologyOutlinedIcon from "@mui/icons-material/PsychologyOutlined";
import ScienceOutlinedIcon from "@mui/icons-material/ScienceOutlined";
import {
	Alert,
	Box,
	Button,
	Chip,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	FormControl,
	FormControlLabel,
	IconButton,
	InputLabel,
	MenuItem,
	Paper,
	Select,
	type SelectChangeEvent,
	Switch,
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
import {
	deleteLlmProfile,
	fetchLlmProfiles,
	patchLlmProfile,
	postLlmProfile,
	postLlmProfileTest,
} from "../../../api/barcodileClient";
import type { LlmProfileDto, LlmProfileKind } from "../../../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1100,
	mx: "auto",
} as const;

function kindLabel(kind: LlmProfileKind): string {
	return kind === "openai" ? "OpenAI" : "OpenAI-compatible";
}

export function LlmProfilesPage() {
	const [rows, setRows] = useState<LlmProfileDto[]>([]);
	const [loading, setLoading] = useState(true);
	const [listError, setListError] = useState<string | null>(null);
	const [deleteTarget, setDeleteTarget] = useState<LlmProfileDto | null>(null);
	const [deleting, setDeleting] = useState(false);
	const [addOpen, setAddOpen] = useState(false);
	const [editTarget, setEditTarget] = useState<LlmProfileDto | null>(null);
	const [formKind, setFormKind] = useState<LlmProfileKind>("openai");
	const [formLabel, setFormLabel] = useState("");
	const [formModel, setFormModel] = useState("");
	const [formBaseUrl, setFormBaseUrl] = useState("");
	const [formApiKey, setFormApiKey] = useState("");
	const [formEnabled, setFormEnabled] = useState(true);
	const [formError, setFormError] = useState<string | null>(null);
	const [saving, setSaving] = useState(false);
	const [testTarget, setTestTarget] = useState<LlmProfileDto | null>(null);
	const [testBusy, setTestBusy] = useState(false);
	const [testResult, setTestResult] = useState<{
		ok: boolean;
		preview: string;
	} | null>(null);

	const load = useCallback(async () => {
		setListError(null);
		setLoading(true);
		try {
			const t = await fetchLlmProfiles();
			setRows(t);
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Request failed");
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		void load();
	}, [load]);

	function resetForm() {
		setFormKind("openai");
		setFormLabel("");
		setFormModel("");
		setFormBaseUrl("");
		setFormApiKey("");
		setFormEnabled(true);
		setFormError(null);
	}

	function openAdd() {
		resetForm();
		setAddOpen(true);
	}

	function openEdit(row: LlmProfileDto) {
		setFormKind(row.kind);
		setFormLabel(row.label);
		setFormModel(row.model);
		setFormBaseUrl(row.baseUrl ?? "");
		setFormApiKey("");
		setFormEnabled(row.enabled);
		setFormError(null);
		setEditTarget(row);
	}

	async function submitCreate() {
		setFormError(null);
		setSaving(true);
		try {
			const body: Parameters<typeof postLlmProfile>[0] = {
				kind: formKind,
				label: formLabel.trim(),
				model: formModel.trim(),
				apiKey: formApiKey.trim(),
				enabled: formEnabled,
			};
			if (formKind === "openai_compatible") {
				body.baseUrl = formBaseUrl.trim();
			}
			await postLlmProfile(body);
			setAddOpen(false);
			resetForm();
			await load();
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	async function submitEdit() {
		if (!editTarget) {
			return;
		}
		setFormError(null);
		setSaving(true);
		try {
			const patch: Record<string, unknown> = {
				kind: formKind,
				label: formLabel.trim(),
				model: formModel.trim(),
				enabled: formEnabled,
			};
			if (formKind === "openai_compatible") {
				patch.baseUrl = formBaseUrl.trim();
			} else {
				patch.baseUrl = null;
			}
			if (formApiKey.trim() !== "") {
				patch.apiKey = formApiKey.trim();
			}
			await patchLlmProfile(editTarget.id, patch);
			setEditTarget(null);
			resetForm();
			await load();
		} catch (e) {
			setFormError(e instanceof Error ? e.message : "Save failed");
		} finally {
			setSaving(false);
		}
	}

	async function confirmDelete() {
		if (!deleteTarget) {
			return;
		}
		setDeleting(true);
		try {
			await deleteLlmProfile(deleteTarget.id);
			setDeleteTarget(null);
			await load();
		} catch (e) {
			setListError(e instanceof Error ? e.message : "Delete failed");
			setDeleteTarget(null);
		} finally {
			setDeleting(false);
		}
	}

	async function runTest(row: LlmProfileDto) {
		setTestTarget(row);
		setTestResult(null);
		setTestBusy(true);
		try {
			const r = await postLlmProfileTest(row.id);
			setTestResult({ ok: r.ok, preview: r.preview });
		} catch (e) {
			setTestResult({
				ok: false,
				preview: e instanceof Error ? e.message : "Request failed",
			});
		} finally {
			setTestBusy(false);
		}
	}

	return (
		<Box>
			<Box
				sx={{
					display: "flex",
					alignItems: "center",
					gap: 1.5,
					mb: 2,
				}}
			>
				<PsychologyOutlinedIcon color="primary" />
				<Typography variant="h5" component="h1" sx={{ fontWeight: 700 }}>
					LLM models
				</Typography>
			</Box>
			<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
				Configure OpenAI or OpenAI-compatible endpoints (for example Ollama or
				LiteLLM). API keys are stored encrypted on the server.
			</Typography>

			{listError ? (
				<Alert severity="error" sx={{ mb: 2 }}>
					{listError}
				</Alert>
			) : null}

			<Paper elevation={0} sx={paperSx}>
				<Box
					sx={{
						display: "flex",
						flexDirection: { xs: "column", sm: "row" },
						justifyContent: "space-between",
						alignItems: { xs: "stretch", sm: "center" },
						gap: 2,
						mb: 2,
					}}
				>
					<Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
						Profiles
					</Typography>
					<Button
						variant="contained"
						startIcon={<AddIcon />}
						onClick={() => openAdd()}
					>
						Add profile
					</Button>
				</Box>

				{loading ? (
					<Typography variant="body2" color="text.secondary">
						Loading…
					</Typography>
				) : (
					<TableContainer>
						<Table size="small">
							<TableHead>
								<TableRow>
									<TableCell>Label</TableCell>
									<TableCell>Kind</TableCell>
									<TableCell>Model</TableCell>
									<TableCell>Base URL</TableCell>
									<TableCell>Key</TableCell>
									<TableCell>Enabled</TableCell>
									<TableCell align="right">Actions</TableCell>
								</TableRow>
							</TableHead>
							<TableBody>
								{rows.length === 0 ? (
									<TableRow>
										<TableCell colSpan={7}>
											<Typography variant="body2" color="text.secondary">
												No profiles yet.
											</Typography>
										</TableCell>
									</TableRow>
								) : (
									rows.map((row) => (
										<TableRow key={row.id} hover>
											<TableCell>{row.label}</TableCell>
											<TableCell>{kindLabel(row.kind)}</TableCell>
											<TableCell>
												<code>{row.model}</code>
											</TableCell>
											<TableCell>
												{row.baseUrl ? (
													<Typography
														variant="body2"
														sx={{ wordBreak: "break-all" }}
													>
														{row.baseUrl}
													</Typography>
												) : (
													"—"
												)}
											</TableCell>
											<TableCell>
												{row.hasStoredApiKey ? (
													<Chip size="small" label="Stored" />
												) : (
													<Chip size="small" variant="outlined" label="None" />
												)}
											</TableCell>
											<TableCell>{row.enabled ? "Yes" : "No"}</TableCell>
											<TableCell align="right">
												<Button
													size="small"
													startIcon={<ScienceOutlinedIcon />}
													onClick={() => void runTest(row)}
												>
													Test
												</Button>
												<Button size="small" onClick={() => openEdit(row)}>
													Edit
												</Button>
												<IconButton
													size="small"
													aria-label="Delete"
													onClick={() => setDeleteTarget(row)}
												>
													<DeleteOutlinedIcon fontSize="small" />
												</IconButton>
											</TableCell>
										</TableRow>
									))
								)}
							</TableBody>
						</Table>
					</TableContainer>
				)}
			</Paper>

			<Dialog open={addOpen} onClose={() => setAddOpen(false)} fullWidth maxWidth="sm">
				<DialogTitle>Add LLM profile</DialogTitle>
				<DialogContent>
					<Box sx={{ display: "flex", flexDirection: "column", gap: 2, mt: 1 }}>
						{formError ? (
							<Alert severity="error">{formError}</Alert>
						) : null}
						<FormControl fullWidth>
							<InputLabel id="add-kind-label">Provider</InputLabel>
							<Select<LlmProfileKind>
								labelId="add-kind-label"
								label="Provider"
								value={formKind}
								onChange={(e: SelectChangeEvent<LlmProfileKind>) =>
									setFormKind(e.target.value as LlmProfileKind)
								}
							>
								<MenuItem value="openai">OpenAI</MenuItem>
								<MenuItem value="openai_compatible">
									OpenAI-compatible (custom base URL)
								</MenuItem>
							</Select>
						</FormControl>
						<TextField
							label="Label"
							value={formLabel}
							onChange={(e) => setFormLabel(e.target.value)}
							fullWidth
							required
						/>
						<TextField
							label="Model id"
							value={formModel}
							onChange={(e) => setFormModel(e.target.value)}
							fullWidth
							required
							helperText="For example gpt-4o-mini or a model name your server exposes."
						/>
						{formKind === "openai_compatible" ? (
							<TextField
								label="Base URL"
								value={formBaseUrl}
								onChange={(e) => setFormBaseUrl(e.target.value)}
								fullWidth
								required
								helperText="Example: http://127.0.0.1:11434 for Ollama (no trailing slash required)."
							/>
						) : null}
						<TextField
							label="API key"
							value={formApiKey}
							onChange={(e) => setFormApiKey(e.target.value)}
							fullWidth
							type="password"
							autoComplete="off"
							required={formKind === "openai"}
							helperText={
								formKind === "openai"
									? "Must start with sk-."
									: "Optional for some local servers; leave empty if unused."
							}
						/>
						<FormControlLabel
							control={
								<Switch
									checked={formEnabled}
									onChange={(e) => setFormEnabled(e.target.checked)}
								/>
							}
							label="Enabled"
						/>
					</Box>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setAddOpen(false)}>Cancel</Button>
					<Button
						variant="contained"
						disabled={saving}
						onClick={() => void submitCreate()}
					>
						Save
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog
				open={editTarget !== null}
				onClose={() => setEditTarget(null)}
				fullWidth
				maxWidth="sm"
			>
				<DialogTitle>Edit LLM profile</DialogTitle>
				<DialogContent>
					<Box sx={{ display: "flex", flexDirection: "column", gap: 2, mt: 1 }}>
						{formError ? (
							<Alert severity="error">{formError}</Alert>
						) : null}
						<FormControl fullWidth>
							<InputLabel id="edit-kind-label">Provider</InputLabel>
							<Select<LlmProfileKind>
								labelId="edit-kind-label"
								label="Provider"
								value={formKind}
								onChange={(e: SelectChangeEvent<LlmProfileKind>) =>
									setFormKind(e.target.value as LlmProfileKind)
								}
							>
								<MenuItem value="openai">OpenAI</MenuItem>
								<MenuItem value="openai_compatible">
									OpenAI-compatible (custom base URL)
								</MenuItem>
							</Select>
						</FormControl>
						<TextField
							label="Label"
							value={formLabel}
							onChange={(e) => setFormLabel(e.target.value)}
							fullWidth
							required
						/>
						<TextField
							label="Model id"
							value={formModel}
							onChange={(e) => setFormModel(e.target.value)}
							fullWidth
							required
						/>
						{formKind === "openai_compatible" ? (
							<TextField
								label="Base URL"
								value={formBaseUrl}
								onChange={(e) => setFormBaseUrl(e.target.value)}
								fullWidth
								required
							/>
						) : null}
						<TextField
							label="New API key (optional)"
							value={formApiKey}
							onChange={(e) => setFormApiKey(e.target.value)}
							fullWidth
							type="password"
							autoComplete="off"
							helperText="Leave blank to keep the current key."
						/>
						<FormControlLabel
							control={
								<Switch
									checked={formEnabled}
									onChange={(e) => setFormEnabled(e.target.checked)}
								/>
							}
							label="Enabled"
						/>
					</Box>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setEditTarget(null)}>Cancel</Button>
					<Button
						variant="contained"
						disabled={saving}
						onClick={() => void submitEdit()}
					>
						Save
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog open={deleteTarget !== null} onClose={() => setDeleteTarget(null)}>
				<DialogTitle>Delete profile?</DialogTitle>
				<DialogContent>
					<Typography variant="body2">
						This removes{" "}
						<strong>{deleteTarget?.label ?? ""}</strong> and its stored credentials.
					</Typography>
				</DialogContent>
				<DialogActions>
					<Button onClick={() => setDeleteTarget(null)}>Cancel</Button>
					<Button
						color="error"
						variant="contained"
						disabled={deleting}
						onClick={() => void confirmDelete()}
					>
						Delete
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog
				open={testTarget !== null}
				onClose={() => {
					setTestTarget(null);
					setTestResult(null);
				}}
				fullWidth
				maxWidth="sm"
			>
				<DialogTitle>
					Test: {testTarget?.label ?? ""}
					{testBusy ? "…" : ""}
				</DialogTitle>
				<DialogContent>
					{testResult ? (
						<Alert severity={testResult.ok ? "success" : "warning"}>
							{testResult.ok ? "Connection OK. " : ""}
							<Typography
								component="span"
								variant="body2"
								sx={{ whiteSpace: "pre-wrap", wordBreak: "break-word" }}
							>
								{testResult.preview}
							</Typography>
						</Alert>
					) : (
						<Typography variant="body2" color="text.secondary">
							Running a minimal completion…
						</Typography>
					)}
				</DialogContent>
				<DialogActions>
					<Button
						onClick={() => {
							setTestTarget(null);
							setTestResult(null);
						}}
					>
						Close
					</Button>
				</DialogActions>
			</Dialog>
		</Box>
	);
}
