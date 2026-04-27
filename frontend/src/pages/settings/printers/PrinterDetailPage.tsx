import ArrowBackIcon from "@mui/icons-material/ArrowBack";
import PrintOutlinedIcon from "@mui/icons-material/PrintOutlined";
import {
	Alert,
	Box,
	Button,
	CircularProgress,
	Paper,
	Table,
	TableBody,
	TableCell,
	TableContainer,
	TableHead,
	TableRow,
	Typography,
} from "@mui/material";
import { useCallback, useEffect, useState } from "react";
import { Link as RouterLink, useParams } from "react-router-dom";
import {
	fetchPrinterDevice,
	fetchPrinterPrintedLabels,
	postPrinterPrintedLabelResend,
	postPrinterTestPrint,
} from "../../../api/barcodileClient";
import type {
	PrintedLabelDto,
	PrintedLabelId,
	PrinterDeviceDto,
} from "../../../domain/barcodile";

const paperSx = {
	p: { xs: 2.5, sm: 3.5 },
	borderRadius: 2,
	border: "1px solid",
	borderColor: "divider",
	maxWidth: 1200,
	mx: "auto",
} as const;

export function PrinterDetailPage() {
	const { id } = useParams<{ id: string }>();
	const [device, setDevice] = useState<PrinterDeviceDto | null>(null);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState<string | null>(null);
	const [printing, setPrinting] = useState(false);
	const [printOk, setPrintOk] = useState<string | null>(null);
	const [printedLabels, setPrintedLabels] = useState<PrintedLabelDto[]>([]);
	const [labelsLoading, setLabelsLoading] = useState(false);
	const [resendingId, setResendingId] = useState<PrintedLabelId | null>(null);

	const load = useCallback(async () => {
		if (!id) {
			setError("Missing printer id.");
			setLoading(false);
			return;
		}
		setError(null);
		setLoading(true);
		try {
			const d = await fetchPrinterDevice(id);
			setDevice(d);
			setLabelsLoading(true);
			setPrintedLabels(await fetchPrinterPrintedLabels(id));
		} catch (e) {
			setError(e instanceof Error ? e.message : "Request failed");
			setDevice(null);
		} finally {
			setLoading(false);
			setLabelsLoading(false);
		}
	}, [id]);

	useEffect(() => {
		void load();
	}, [load]);

	async function onTestPrint() {
		if (!id) {
			return;
		}
		setPrintOk(null);
		setPrinting(true);
		setError(null);
		try {
			const r = await postPrinterTestPrint(id);
			setPrintOk(
				r.status === "queued"
					? "Test label sent to the printer."
					: `Status: ${r.status}`,
			);
			setPrintedLabels(await fetchPrinterPrintedLabels(id));
		} catch (e) {
			setError(e instanceof Error ? e.message : "Print failed");
		} finally {
			setPrinting(false);
		}
	}

	async function onResendLabel(printedLabelId: PrintedLabelId) {
		if (!id) {
			return;
		}
		setPrintOk(null);
		setError(null);
		setResendingId(printedLabelId);
		try {
			const r = await postPrinterPrintedLabelResend(id, printedLabelId);
			setPrintOk(
				r.status === "queued"
					? "Label sent to the printer again."
					: `Status: ${r.status}`,
			);
			setPrintedLabels(await fetchPrinterPrintedLabels(id));
		} catch (e) {
			setError(e instanceof Error ? e.message : "Resend failed");
		} finally {
			setResendingId(null);
		}
	}

	return (
		<Paper elevation={0} sx={paperSx}>
			<Button
				component={RouterLink}
				to="/settings/printers"
				startIcon={<ArrowBackIcon />}
				sx={{ mb: 2 }}
			>
				Printers
			</Button>
			{loading ? (
				<Box sx={{ display: "flex", alignItems: "center", gap: 2 }}>
					<CircularProgress size={28} />
					<Typography color="text.secondary">Loading...</Typography>
				</Box>
			) : error && !device ? (
				<Alert severity="error">{error}</Alert>
			) : device ? (
				<>
					<Typography variant="h5" sx={{ fontWeight: 700, mb: 2 }}>
						{device.name}
					</Typography>
					<Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
						Driver:{" "}
						<Box
							component="span"
							sx={{ fontFamily: "ui-monospace, monospace" }}
						>
							{device.driverCode}
						</Box>
					</Typography>
					<Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
						Connection
					</Typography>
					<Typography
						component="pre"
						variant="body2"
						sx={{
							fontFamily: "ui-monospace, monospace",
							fontSize: 12,
							wordBreak: "break-all",
							whiteSpace: "pre-wrap",
							mb: 2,
							p: 1.5,
							borderRadius: 1,
							border: "1px solid",
							borderColor: "divider",
							bgcolor: "action.hover",
						}}
					>
						{JSON.stringify(device.connection, null, 2)}
					</Typography>
					<Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
						Print settings
					</Typography>
					<Typography
						component="pre"
						variant="body2"
						sx={{
							fontFamily: "ui-monospace, monospace",
							fontSize: 12,
							wordBreak: "break-all",
							whiteSpace: "pre-wrap",
							mb: 2,
							p: 1.5,
							borderRadius: 1,
							border: "1px solid",
							borderColor: "divider",
							bgcolor: "action.hover",
						}}
					>
						{JSON.stringify(device.printSettings, null, 2)}
					</Typography>
					{error && (
						<Alert
							severity="error"
							sx={{ mb: 2 }}
							onClose={() => setError(null)}
						>
							{error}
						</Alert>
					)}
					{printOk && (
						<Alert
							severity="success"
							sx={{ mb: 2 }}
							onClose={() => setPrintOk(null)}
						>
							{printOk}
						</Alert>
					)}
					<Button
						variant="contained"
						startIcon={<PrintOutlinedIcon />}
						onClick={() => void onTestPrint()}
						disabled={printing}
					>
						{printing ? "Printing..." : "Print test label"}
					</Button>
					<Box sx={{ mt: 4 }}>
						<Typography variant="h6" sx={{ fontWeight: 700, mb: 1 }}>
							Printed labels
						</Typography>
						<Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
							Latest 100 PNG labels sent to this printer.
						</Typography>
						{labelsLoading ? (
							<Box sx={{ display: "flex", alignItems: "center", gap: 2 }}>
								<CircularProgress size={24} />
								<Typography color="text.secondary">
									Loading labels...
								</Typography>
							</Box>
						) : printedLabels.length === 0 ? (
							<Typography color="text.secondary">
								No printed labels recorded yet.
							</Typography>
						) : (
							<TableContainer
								component={Paper}
								variant="outlined"
								sx={{ maxHeight: 640 }}
							>
								<Table stickyHeader size="small">
									<TableHead>
										<TableRow>
											<TableCell>Label</TableCell>
											<TableCell>Printed</TableCell>
											<TableCell>Size</TableCell>
											<TableCell>Source</TableCell>
											<TableCell align="right">Action</TableCell>
										</TableRow>
									</TableHead>
									<TableBody>
										{printedLabels.map((label) => (
											<TableRow key={label.id} hover>
												<TableCell>
													<Box
														component="img"
														src={label.imageUrl}
														alt={`Printed label ${label.id}`}
														sx={{
															display: "block",
															width: 160,
															height: 96,
															objectFit: "contain",
															border: "1px solid",
															borderColor: "divider",
															borderRadius: 1,
															bgcolor: "background.default",
														}}
													/>
												</TableCell>
												<TableCell>
													{new Date(label.createdAt).toLocaleString()}
												</TableCell>
												<TableCell>
													{label.labelWidthMillimeters} x{" "}
													{label.labelHeightMillimeters} mm
												</TableCell>
												<TableCell>{label.source}</TableCell>
												<TableCell align="right">
													<Button
														size="small"
														variant="outlined"
														startIcon={<PrintOutlinedIcon />}
														onClick={() => void onResendLabel(label.id)}
														disabled={resendingId !== null}
													>
														{resendingId === label.id ? "Sending..." : "Resend"}
													</Button>
												</TableCell>
											</TableRow>
										))}
									</TableBody>
								</Table>
							</TableContainer>
						)}
					</Box>
				</>
			) : null}
		</Paper>
	);
}
