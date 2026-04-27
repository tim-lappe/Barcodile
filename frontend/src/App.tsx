import { useEffect, useState } from "react";
import { Navigate, Route, Routes, useParams } from "react-router-dom";
import { fetchCatalogItemsFlat } from "./api/barcodileClient";
import { readJsonArray } from "./api/collection";
import type { CatalogItemDto, InventoryItemDto } from "./domain/barcodile";
import { AdminLayout } from "./layout/AdminLayout";
import { SettingsLayout } from "./layout/SettingsLayout";
import { ActivityPage } from "./pages/ActivityPage";
import { CatalogItemFormPage } from "./pages/CatalogItemFormPage";
import { CatalogItemsPage } from "./pages/CatalogItemsPage";
import { DashboardPage } from "./pages/DashboardPage";
import { ShoppingCartDetailPage } from "./pages/ShoppingCartDetailPage";
import { ShoppingCartNewPage } from "./pages/ShoppingCartNewPage";
import { ShoppingCartsPage } from "./pages/ShoppingCartsPage";
import { StockItemFormPage } from "./pages/StockItemFormPage";
import { StockPage } from "./pages/StockPage";
import { LogsPage } from "./pages/settings/debug/LogsPage";
import { LocationFormPage } from "./pages/settings/locations/LocationFormPage";
import { LocationsPage } from "./pages/settings/locations/LocationsPage";
import { PicnicSettingsPage } from "./pages/settings/PicnicSettingsPage";
import { PrinterDetailPage } from "./pages/settings/printers/PrinterDetailPage";
import { PrintersPage } from "./pages/settings/printers/PrintersPage";
import { ScannerDetailPage } from "./pages/settings/scanner/ScannerDetailPage";
import { ScannerPage } from "./pages/settings/scanner/ScannerPage";
import { PicnicConnectionProvider } from "./picnic/PicnicConnectionProvider";

function CatalogItemFormRoute() {
	const { id } = useParams();
	return <CatalogItemFormPage key={id ?? "new"} />;
}

export default function App() {
	const [catalogItems, setCatalogItems] = useState<CatalogItemDto[]>([]);
	const [inventoryItems, setInventoryItems] = useState<InventoryItemDto[]>([]);
	const [error, setError] = useState<string | null>(null);
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		let cancelled = false;
		(async () => {
			setLoading(true);
			setError(null);
			try {
				const [types, inventoryRes] = await Promise.all([
					fetchCatalogItemsFlat(),
					fetch("/api/inventory_items", {
						headers: { Accept: "application/json" },
					}),
				]);
				if (!inventoryRes.ok) {
					throw new Error(
						`inventory_items: ${inventoryRes.status} ${inventoryRes.statusText}`,
					);
				}
				const inventoryJson: unknown = await inventoryRes.json();
				if (!cancelled) {
					setCatalogItems(types);
					setInventoryItems(readJsonArray<InventoryItemDto>(inventoryJson));
				}
			} catch (e) {
				if (!cancelled) {
					setError(e instanceof Error ? e.message : "Request failed");
				}
			} finally {
				if (!cancelled) {
					setLoading(false);
				}
			}
		})();
		return () => {
			cancelled = true;
		};
	}, []);

	return (
		<PicnicConnectionProvider>
			<AdminLayout>
				<Routes>
					<Route path="/settings/*" element={<SettingsLayout />}>
						<Route index element={<Navigate to="scanner" replace />} />
						<Route path="scanner" element={<ScannerPage />} />
						<Route path="scanner/:id" element={<ScannerDetailPage />} />
						<Route path="printers" element={<PrintersPage />} />
						<Route path="printers/:id" element={<PrinterDetailPage />} />
						<Route path="locations" element={<LocationsPage />} />
						<Route path="locations/new" element={<LocationFormPage />} />
						<Route path="locations/:id/edit" element={<LocationFormPage />} />
						<Route path="picnic" element={<PicnicSettingsPage />} />
						<Route path="debug/logs" element={<LogsPage />} />
					</Route>
					<Route path="/picnic/*" element={<Navigate to="/carts" replace />} />
					<Route
						path="/locations"
						element={<Navigate to="/settings/locations" replace />}
					/>
					<Route
						path="/locations/new"
						element={<Navigate to="/settings/locations/new" replace />}
					/>
					<Route
						path="/locations/:id/edit"
						element={<NavigateLocationEditToSettings />}
					/>
					<Route
						path="/devices"
						element={<Navigate to="/settings/scanner" replace />}
					/>
					<Route
						path="/devices/:id"
						element={<NavigateScannerDetailToSettings />}
					/>
					<Route
						path="/printers"
						element={<Navigate to="/settings/printers" replace />}
					/>
					<Route
						path="/printers/:id"
						element={<NavigatePrinterDetailToSettings />}
					/>
					<Route
						path="/"
						element={
							<DashboardPage
								catalogItems={catalogItems}
								inventoryItems={inventoryItems}
								error={error}
								loading={loading}
							/>
						}
					/>
					<Route path="/catalog-items" element={<CatalogItemsPage />} />
					<Route path="/catalog-items/new" element={<CatalogItemFormRoute />} />
					<Route
						path="/catalog-items/:id/edit"
						element={<CatalogItemFormRoute />}
					/>
					<Route path="/inventory" element={<StockPage />} />
					<Route path="/inventory/new" element={<StockItemFormPage />} />
					<Route path="/inventory/:id/edit" element={<StockItemFormPage />} />
					<Route path="/carts" element={<ShoppingCartsPage />} />
					<Route path="/carts/new" element={<ShoppingCartNewPage />} />
					<Route path="/carts/:id" element={<ShoppingCartDetailPage />} />
					<Route path="/activity" element={<ActivityPage />} />
				</Routes>
			</AdminLayout>
		</PicnicConnectionProvider>
	);
}

function NavigateScannerDetailToSettings() {
	const { id } = useParams();
	return <Navigate to={`/settings/scanner/${id ?? ""}`} replace />;
}

function NavigatePrinterDetailToSettings() {
	const { id } = useParams();
	return <Navigate to={`/settings/printers/${id ?? ""}`} replace />;
}

function NavigateLocationEditToSettings() {
	const { id } = useParams();
	return <Navigate to={`/settings/locations/${id ?? ""}/edit`} replace />;
}
