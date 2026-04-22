import { useEffect, useState } from "react";
import { Navigate, Route, Routes, useParams } from "react-router-dom";
import { fetchCatalogItemsFlat } from "./api/barcodileClient";
import { readJsonArray } from "./api/collection";
import type { CatalogItemDto, InventoryItemDto } from "./domain/barcodile";
import { AdminLayout } from "./layout/AdminLayout";
import { SettingsLayout } from "./layout/SettingsLayout";
import { CatalogItemFormPage } from "./pages/CatalogItemFormPage";
import { CatalogItemsPage } from "./pages/CatalogItemsPage";
import { DashboardPage } from "./pages/DashboardPage";
import { ActivityPage } from "./pages/ActivityPage";
import { DevicesPage } from "./pages/DevicesPage";
import { LocationFormPage } from "./pages/LocationFormPage";
import { LocationsPage } from "./pages/LocationsPage";
import { ShoppingCartDetailPage } from "./pages/ShoppingCartDetailPage";
import { ShoppingCartNewPage } from "./pages/ShoppingCartNewPage";
import { ShoppingCartsPage } from "./pages/ShoppingCartsPage";
import { StockItemFormPage } from "./pages/StockItemFormPage";
import { StockPage } from "./pages/StockPage";
import { PicnicSettingsPage } from "./pages/settings/PicnicSettingsPage";
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
						<Route index element={<Navigate to="picnic" replace />} />
						<Route path="picnic" element={<PicnicSettingsPage />} />
					</Route>
					<Route path="/picnic/*" element={<Navigate to="/carts" replace />} />
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
					<Route path="/locations" element={<LocationsPage />} />
					<Route path="/locations/new" element={<LocationFormPage />} />
					<Route path="/locations/:id/edit" element={<LocationFormPage />} />
					<Route path="/inventory" element={<StockPage />} />
					<Route path="/inventory/new" element={<StockItemFormPage />} />
					<Route path="/inventory/:id/edit" element={<StockItemFormPage />} />
					<Route path="/carts" element={<ShoppingCartsPage />} />
					<Route path="/carts/new" element={<ShoppingCartNewPage />} />
					<Route path="/carts/:id" element={<ShoppingCartDetailPage />} />
					<Route path="/devices" element={<DevicesPage />} />
					<Route path="/activity" element={<ActivityPage />} />
				</Routes>
			</AdminLayout>
		</PicnicConnectionProvider>
	);
}
