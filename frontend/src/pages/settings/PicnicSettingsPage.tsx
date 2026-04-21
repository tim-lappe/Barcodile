import CheckCircleRoundedIcon from "@mui/icons-material/CheckCircleRounded";
import DeleteOutlineRoundedIcon from "@mui/icons-material/DeleteOutlineRounded";
import {
	Alert,
	Box,
	Button,
	Card,
	CardContent,
	Chip,
	CircularProgress,
	Dialog,
	DialogActions,
	DialogContent,
	DialogTitle,
	FormControl,
	IconButton,
	InputLabel,
	MenuItem,
	Select,
	Stack,
	TextField,
	Typography,
} from "@mui/material";
import { alpha } from "@mui/material/styles";
import { useCallback, useEffect, useState } from "react";
import {
	fetchPicnicIntegrationSettings,
	patchPicnicIntegrationSettings,
	picnicLogin,
	picnicRequestTwoFactorCode,
} from "../../api/barcodileClient";
import type {
	PicnicCountryCode,
	PicnicIntegrationSettingsDto,
} from "../../domain/barcodile";
import { usePicnicConnection } from "../../picnic/usePicnicConnection";

const MASKED_PASSWORD = "****";

type PicnicTwoFactorChannel = "SMS" | "EMAIL";

export function PicnicSettingsPage() {
	const { notifyPicnicSessionFromDto } = usePicnicConnection();
	const [loading, setLoading] = useState(true);
	const [saving, setSaving] = useState(false);
	const [loginBusy, setLoginBusy] = useState(false);
	const [error, setError] = useState<string | null>(null);
	const [loginSuccessMessage, setLoginSuccessMessage] = useState<string | null>(
		null,
	);
	const [dto, setDto] = useState<PicnicIntegrationSettingsDto | null>(null);
	const [passwordInput, setPasswordInput] = useState("");
	const [passwordEditing, setPasswordEditing] = useState(false);
	const [otpDialogOpen, setOtpDialogOpen] = useState(false);
	const [pendingToken, setPendingToken] = useState<string | null>(null);
	const [otpInput, setOtpInput] = useState("");
	const [twoFactorChannel, setTwoFactorChannel] =
		useState<PicnicTwoFactorChannel>("SMS");
	const [request2faBusy, setRequest2faBusy] = useState(false);
	const [twoFaDialogFeedback, setTwoFaDialogFeedback] = useState<{
		severity: "success" | "error";
		message: string;
	} | null>(null);
	const [clearCredentialsDialogOpen, setClearCredentialsDialogOpen] =
		useState(false);

	const load = useCallback(async () => {
		setLoading(true);
		setError(null);
		try {
			const data = await fetchPicnicIntegrationSettings();
			setDto(data);
			notifyPicnicSessionFromDto(data);
			setPasswordInput("");
			setPasswordEditing(false);
		} catch (e) {
			setError(e instanceof Error ? e.message : "Failed to load settings");
		} finally {
			setLoading(false);
		}
	}, [notifyPicnicSessionFromDto]);

	useEffect(() => {
		void load();
	}, [load]);

	async function patchSettings(patch: Record<string, unknown>) {
		setError(null);
		setLoginSuccessMessage(null);
		try {
			const next = await patchPicnicIntegrationSettings(patch);
			setDto(next);
			notifyPicnicSessionFromDto(next);
		} catch (err) {
			setError(err instanceof Error ? err.message : "Update failed");
			await load();
		}
	}

	async function handleClearStoredCredentials() {
		setSaving(true);
		setError(null);
		setLoginSuccessMessage(null);
		try {
			const next = await patchPicnicIntegrationSettings({ password: "" });
			setClearCredentialsDialogOpen(false);
			setDto(next);
			notifyPicnicSessionFromDto(next);
			setPasswordInput("");
			setPasswordEditing(false);
		} catch (err) {
			setError(err instanceof Error ? err.message : "Update failed");
		} finally {
			setSaving(false);
		}
	}

	function buildLoginBody(): {
		username: string;
		countryCode: PicnicCountryCode;
		password?: string;
	} | null {
		if (!dto) {
			return null;
		}
		const username = (dto.username ?? "").trim();
		if (username === "") {
			setError("Enter a username before logging in.");
			return null;
		}
		const useStored =
			dto.hasStoredPassword && (!passwordEditing || passwordInput === "");
		if (!dto.hasStoredPassword && passwordInput.trim() === "") {
			setError("Enter a password before logging in.");
			return null;
		}
		const body: {
			username: string;
			countryCode: PicnicCountryCode;
			password?: string;
		} = {
			username,
			countryCode: dto.countryCode,
		};
		if (!useStored) {
			body.password = passwordInput;
		}
		return body;
	}

	async function handleLogin() {
		if (!dto) {
			return;
		}
		setError(null);
		setLoginSuccessMessage(null);
		const body = buildLoginBody();
		if (!body) {
			return;
		}
		setLoginBusy(true);
		try {
			const result = await picnicLogin(body);
			if (!result.ok) {
				setError(result.message);
				return;
			}
			if (result.secondFactorAuthenticationRequired) {
				setPendingToken(result.pendingToken);
				setOtpInput("");
				setTwoFactorChannel("SMS");
				setTwoFaDialogFeedback(null);
				setOtpDialogOpen(true);
				return;
			}
			await load();
			setPasswordInput("");
			setPasswordEditing(false);
			setLoginSuccessMessage(result.message);
		} catch (err) {
			setError(err instanceof Error ? err.message : "Login failed");
		} finally {
			setLoginBusy(false);
		}
	}

	async function handleOtpSubmit() {
		if (pendingToken === null) {
			return;
		}
		const otp = otpInput.trim();
		if (otp === "") {
			setTwoFaDialogFeedback({
				severity: "error",
				message: "Enter the verification code.",
			});
			return;
		}
		setTwoFaDialogFeedback(null);
		setLoginBusy(true);
		try {
			const result = await picnicLogin({ pendingToken, otp });
			if (!result.ok) {
				setTwoFaDialogFeedback({ severity: "error", message: result.message });
				return;
			}
			setOtpDialogOpen(false);
			setPendingToken(null);
			setOtpInput("");
			setTwoFaDialogFeedback(null);
			await load();
			setPasswordInput("");
			setPasswordEditing(false);
			setLoginSuccessMessage(result.message);
		} catch (err) {
			setTwoFaDialogFeedback({
				severity: "error",
				message: err instanceof Error ? err.message : "Verification failed",
			});
		} finally {
			setLoginBusy(false);
		}
	}

	async function handleRequestPicnicTwoFactorCode() {
		if (pendingToken === null) {
			return;
		}
		setTwoFaDialogFeedback(null);
		setRequest2faBusy(true);
		try {
			const r = await picnicRequestTwoFactorCode({
				pendingToken,
				channel: twoFactorChannel,
			});
			setTwoFaDialogFeedback({ severity: "success", message: r.message });
		} catch (err) {
			setTwoFaDialogFeedback({
				severity: "error",
				message: err instanceof Error ? err.message : "Request failed",
			});
		} finally {
			setRequest2faBusy(false);
		}
	}

	function handleOtpDialogClose() {
		if (loginBusy || request2faBusy) {
			return;
		}
		setOtpDialogOpen(false);
		setPendingToken(null);
		setOtpInput("");
		setTwoFaDialogFeedback(null);
	}

	if (loading || !dto) {
		return (
			<Box sx={{ display: "flex", justifyContent: "center", py: 6 }}>
				<CircularProgress />
			</Box>
		);
	}

	const passwordFieldType =
		dto.hasStoredPassword && !passwordEditing
			? ("text" as const)
			: ("password" as const);
	const passwordFieldValue =
		dto.hasStoredPassword && !passwordEditing ? MASKED_PASSWORD : passwordInput;

	return (
		<Stack spacing={4} sx={{ maxWidth: 520, width: "100%" }}>
			<Stack spacing={1.25}>
				<Typography variant="h5" sx={{ fontWeight: 700 }}>
					Picnic
				</Typography>
				<Typography variant="body2" color="text.secondary">
					{dto.hasStoredAuthSession
						? "Picnic is ready to use. Remove the stored connection when you want to change account details or set up again."
						: "Connect Barcodile to your Picnic account. Use Login to verify credentials, complete two-factor authentication if needed, and store an encrypted session on the server for API access."}
				</Typography>
			</Stack>

			{error || (loginSuccessMessage && !dto.hasStoredAuthSession) ? (
				<Stack spacing={2}>
					{error ? <Alert severity="error">{error}</Alert> : null}
					{loginSuccessMessage && !dto.hasStoredAuthSession ? (
						<Alert
							severity="success"
							onClose={() => setLoginSuccessMessage(null)}
						>
							{loginSuccessMessage}
						</Alert>
					) : null}
				</Stack>
			) : null}

			{dto.hasStoredAuthSession ? (
				<Card
					elevation={0}
					sx={(theme) => ({
						borderRadius: 2,
						border: "1px solid",
						borderColor: alpha(theme.palette.success.main, 0.35),
						background: `linear-gradient(145deg, ${alpha(theme.palette.success.main, 0.12)} 0%, ${alpha(theme.palette.primary.main, 0.06)} 55%, ${theme.palette.background.paper} 100%)`,
						boxShadow: `0 8px 24px ${alpha(theme.palette.common.black, 0.06)}`,
					})}
				>
					<CardContent sx={{ p: 3, "&:last-child": { pb: 3 } }}>
						<Stack
							sx={{ flexDirection: "row", alignItems: "flex-start", gap: 2 }}
						>
							<CheckCircleRoundedIcon
								sx={{
									fontSize: 44,
									color: "success.main",
									flexShrink: 0,
									mt: 0.25,
								}}
							/>
							<Box sx={{ flex: 1, minWidth: 0 }}>
								<Stack
									sx={{
										flexDirection: "row",
										alignItems: "flex-start",
										justifyContent: "space-between",
										gap: 1,
									}}
								>
									<Box sx={{ minWidth: 0 }}>
										<Typography
											variant="subtitle1"
											sx={{ fontWeight: 700, letterSpacing: -0.02 }}
										>
											Picnic is connected
										</Typography>
										<Typography
											variant="body2"
											color="text.secondary"
											sx={{ mt: 0.5 }}
										>
											Your credentials and session are stored encrypted on this
											server.
										</Typography>
									</Box>
									<IconButton
										aria-label="Remove stored Picnic credentials"
										color="error"
										size="small"
										disabled={saving}
										onClick={() => setClearCredentialsDialogOpen(true)}
										sx={{
											mt: -0.5,
											bgcolor: (theme) => alpha(theme.palette.error.main, 0.08),
											"&:hover": {
												bgcolor: (theme) =>
													alpha(theme.palette.error.main, 0.16),
											},
										}}
									>
										<DeleteOutlineRoundedIcon fontSize="small" />
									</IconButton>
								</Stack>
								<Stack
									sx={{ flexDirection: "row", flexWrap: "wrap", gap: 1, mt: 2 }}
								>
									<Chip
										size="small"
										label={dto.username?.trim() ? dto.username : "No username"}
										variant="outlined"
										sx={{ fontWeight: 500 }}
									/>
									<Chip
										size="small"
										color="primary"
										variant="filled"
										label={
											dto.countryCode === "NL"
												? "Netherlands (NL)"
												: "Germany (DE)"
										}
										sx={(theme) => ({
											fontWeight: 600,
											bgcolor: alpha(theme.palette.primary.main, 0.22),
											color: "primary.dark",
											"&:hover": {
												bgcolor: alpha(theme.palette.primary.main, 0.28),
											},
										})}
									/>
								</Stack>
							</Box>
						</Stack>
					</CardContent>
				</Card>
			) : null}
			{!dto.hasStoredAuthSession ? (
				<Card
					elevation={0}
					sx={(theme) => ({
						borderRadius: 2,
						border: "1px solid",
						borderColor: "divider",
						boxShadow: `0 4px 18px ${alpha(theme.palette.common.black, 0.05)}`,
					})}
				>
					<CardContent
						sx={{
							p: { xs: 2.5, sm: 3 },
							"&:last-child": { pb: { xs: 2.5, sm: 3 } },
						}}
					>
						<Stack spacing={3}>
							<TextField
								label="Username"
								value={dto.username ?? ""}
								onChange={(e) => setDto({ ...dto, username: e.target.value })}
								autoComplete="username"
								fullWidth
								required
							/>

							<TextField
								label="Password"
								type={passwordFieldType}
								value={passwordFieldValue}
								onFocus={() => {
									if (dto.hasStoredPassword && !passwordEditing) {
										setPasswordEditing(true);
										setPasswordInput("");
									}
								}}
								onChange={(e) => setPasswordInput(e.target.value)}
								onBlur={() => {
									if (
										dto.hasStoredPassword &&
										passwordEditing &&
										passwordInput === ""
									) {
										setPasswordEditing(false);
									}
								}}
								autoComplete="new-password"
								fullWidth
								helperText={
									dto.hasStoredPassword
										? "A password is already stored. Focus the field to replace it, or leave the placeholder to keep it when logging in."
										: "Required for the first login unless you use a password already stored on the server."
								}
							/>

							{dto.hasStoredPassword && !dto.hasStoredAuthSession ? (
								<Button
									type="button"
									variant="outlined"
									color="warning"
									disabled={saving}
									onClick={() => setClearCredentialsDialogOpen(true)}
									sx={{ alignSelf: "flex-start" }}
								>
									Remove stored password
								</Button>
							) : null}

							<FormControl fullWidth>
								<InputLabel id="picnic-country-label">Country</InputLabel>
								<Select<PicnicCountryCode>
									labelId="picnic-country-label"
									label="Country"
									value={dto.countryCode}
									onChange={(e) => {
										const next = e.target.value as PicnicCountryCode;
										const prev = dto.countryCode;
										setDto({ ...dto, countryCode: next });
										const patch: Record<string, unknown> = {
											countryCode: next,
										};
										if (prev !== next) {
											patch.clearPicnicAuthSession = true;
										}
										void patchSettings(patch);
									}}
								>
									<MenuItem value="NL">Netherlands (NL)</MenuItem>
									<MenuItem value="DE">Germany (DE)</MenuItem>
								</Select>
							</FormControl>

							<Button
								type="button"
								variant="contained"
								disabled={loginBusy || saving}
								size="large"
								sx={{ alignSelf: "flex-start" }}
								onClick={() => void handleLogin()}
							>
								{loginBusy ? "Logging in…" : "Login"}
							</Button>
						</Stack>
					</CardContent>
				</Card>
			) : null}

			<Dialog
				open={otpDialogOpen}
				onClose={() => handleOtpDialogClose()}
				maxWidth="xs"
				fullWidth
			>
				<DialogTitle>Two-factor authentication</DialogTitle>
				<DialogContent>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
						Enter the code from your authenticator app, or ask Picnic to send a
						code by SMS or email, then enter it below.
					</Typography>
					{twoFaDialogFeedback ? (
						<Alert severity={twoFaDialogFeedback.severity} sx={{ mb: 2 }}>
							{twoFaDialogFeedback.message}
						</Alert>
					) : null}
					<Stack spacing={2} sx={{ mb: 1 }}>
						<FormControl fullWidth size="small">
							<InputLabel id="picnic-2fa-channel-label">
								Delivery channel
							</InputLabel>
							<Select<PicnicTwoFactorChannel>
								labelId="picnic-2fa-channel-label"
								label="Delivery channel"
								value={twoFactorChannel}
								onChange={(e) =>
									setTwoFactorChannel(e.target.value as PicnicTwoFactorChannel)
								}
								disabled={loginBusy || request2faBusy}
							>
								<MenuItem value="SMS">SMS</MenuItem>
								<MenuItem value="EMAIL">Email</MenuItem>
							</Select>
						</FormControl>
						<Button
							type="button"
							variant="outlined"
							disabled={loginBusy || request2faBusy || pendingToken === null}
							onClick={() => void handleRequestPicnicTwoFactorCode()}
						>
							{request2faBusy ? "Requesting…" : "Request code from Picnic"}
						</Button>
					</Stack>
					<TextField
						label="Verification code"
						value={otpInput}
						onChange={(e) => setOtpInput(e.target.value)}
						fullWidth
						autoComplete="one-time-code"
						disabled={loginBusy}
					/>
				</DialogContent>
				<DialogActions sx={{ px: 3, pb: 2 }}>
					<Button
						onClick={() => handleOtpDialogClose()}
						disabled={loginBusy || request2faBusy}
					>
						Cancel
					</Button>
					<Button
						variant="contained"
						onClick={() => void handleOtpSubmit()}
						disabled={loginBusy || request2faBusy}
					>
						{loginBusy ? "Verifying…" : "Continue"}
					</Button>
				</DialogActions>
			</Dialog>

			<Dialog
				open={clearCredentialsDialogOpen}
				onClose={() => !saving && setClearCredentialsDialogOpen(false)}
				slotProps={{
					paper: { sx: { borderRadius: 2 } },
				}}
			>
				<DialogTitle>Remove Picnic credentials?</DialogTitle>
				<DialogContent>
					<Typography variant="body2" color="text.secondary">
						This removes the stored password and session from this server. You
						can connect again with Login whenever you are ready.
					</Typography>
				</DialogContent>
				<DialogActions sx={{ px: 3, pb: 2 }}>
					<Button
						onClick={() => setClearCredentialsDialogOpen(false)}
						disabled={saving}
					>
						Cancel
					</Button>
					<Button
						color="error"
						variant="contained"
						onClick={() => void handleClearStoredCredentials()}
						disabled={saving}
					>
						{saving ? "Removing…" : "Remove"}
					</Button>
				</DialogActions>
			</Dialog>
		</Stack>
	);
}
