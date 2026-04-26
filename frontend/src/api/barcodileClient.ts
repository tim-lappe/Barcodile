import type {
	CartProviderIndexEntryDto,
	CartStockAutomationRuleDto,
	CartStockAutomationRuleId,
	CatalogItemAttributeId,
	CatalogItemAttributeKey,
	CatalogItemDto,
	CatalogItemId,
	InputDeviceOptionDto,
	InventoryItemDto,
	InventoryItemId,
	LocationDto,
	LocationId,
	ScannerDeviceDto,
	ScannerDeviceId,
	PicnicCatalogProductSummaryDto,
	PicnicCatalogSearchHitDto,
	PicnicIntegrationSettingsDto,
	PicnicLoginResponse,
	PicnicRequestTwoFactorCodeResponse,
	ShoppingCartDto,
	ShoppingCartId,
	ShoppingCartLineDto,
	ShoppingCartLineId,
	VolumeDto,
	WeightDto,
	ActivityListDto,
	DiscoveredPrinterOptionDto,
	PrinterDeviceDto,
	PrinterDeviceId,
	PrinterDriverDto,
} from "../domain/barcodile";
import { readJsonArray } from "./collection";

export type CatalogItemAttributeWriteRow = {
	id?: CatalogItemAttributeId;
	attribute: CatalogItemAttributeKey;
	value: unknown | null;
};

const JSON_HEADERS = {
	Accept: "application/json",
	"Content-Type": "application/json",
} as const;

const MERGE_PATCH_HEADERS = {
	Accept: "application/json",
	"Content-Type": "application/merge-patch+json",
} as const;

async function readErrorMessage(res: Response): Promise<string> {
	const text = await res.text();
	try {
		const body = JSON.parse(text) as {
			detail?: string;
			title?: string;
			description?: string;
		};
		return body.detail ?? body.description ?? body.title ?? text;
	} catch {
		return text || `${res.status} ${res.statusText}`;
	}
}

export function locationIri(id: LocationId): string {
	return `/api/locations/${id}`;
}

export function catalogItemIri(id: CatalogItemId): string {
	return `/api/catalog_items/${id}`;
}

export function catalogItemImageUrl(
	id: CatalogItemId,
	cacheBust?: string | number,
): string {
	const path = `/api/catalog_items/${id}/image`;
	if (cacheBust === undefined) {
		return path;
	}
	return `${path}?v=${encodeURIComponent(String(cacheBust))}`;
}

export async function uploadCatalogItemImage(
	id: CatalogItemId,
	file: File,
): Promise<CatalogItemDto> {
	const body = new FormData();
	body.append("file", file);
	const res = await fetch(catalogItemImageUrl(id), {
		method: "POST",
		body,
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as CatalogItemDto;
}

export async function deleteCatalogItemImage(
	id: CatalogItemId,
): Promise<CatalogItemDto> {
	const res = await fetch(catalogItemImageUrl(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as CatalogItemDto;
}

export function inventoryItemIri(id: InventoryItemId): string {
	return `/api/inventory_items/${id}`;
}

export async function fetchLocations(): Promise<LocationDto[]> {
	const res = await fetch("/api/locations", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<LocationDto>(data);
}

export async function fetchLocation(id: LocationId): Promise<LocationDto> {
	const res = await fetch(locationIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as LocationDto;
}

export async function createLocation(input: {
	name: string;
	parentId?: LocationId | null;
}): Promise<LocationDto> {
	const body: Record<string, unknown> = { name: input.name };
	if (input.parentId) {
		body.parent = locationIri(input.parentId);
	}
	const res = await fetch("/api/locations", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as LocationDto;
}

export async function updateLocation(
	id: LocationId,
	input: { name: string; parentId: LocationId | null },
): Promise<void> {
	const body: Record<string, unknown> = {
		name: input.name,
		parent: input.parentId === null ? null : locationIri(input.parentId),
	};
	const res = await fetch(locationIri(id), {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteLocation(id: LocationId): Promise<void> {
	const res = await fetch(locationIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export function scannerDeviceIri(id: ScannerDeviceId): string {
	return `/api/scanner_devices/${id}`;
}

export async function fetchScannerDevices(): Promise<ScannerDeviceDto[]> {
	const res = await fetch("/api/scanner_devices", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<ScannerDeviceDto>(data);
}

export async function fetchScannerDevice(
	id: ScannerDeviceId,
): Promise<ScannerDeviceDto> {
	const res = await fetch(scannerDeviceIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ScannerDeviceDto;
}

export async function patchScannerDeviceAutomations(
	id: ScannerDeviceId,
	body: {
		automationAddInventoryOnEanScan: boolean;
		automationCreateCatalogItemIfMissingForEan: boolean;
		automationRemoveInventoryOnPublicCodeScan: boolean;
	},
): Promise<ScannerDeviceDto> {
	const res = await fetch(scannerDeviceIri(id), {
		method: "PATCH",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ScannerDeviceDto;
}

export async function fetchScannerDeviceInputOptions(): Promise<
	InputDeviceOptionDto[]
> {
	const res = await fetch("/api/scanner_devices/input_device_options", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<InputDeviceOptionDto>(data);
}

export async function postScannerDevice(input: {
	deviceIdentifier: string;
	name: string;
}): Promise<ScannerDeviceDto> {
	const res = await fetch("/api/scanner_devices", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({
			deviceIdentifier: input.deviceIdentifier,
			name: input.name,
		}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ScannerDeviceDto;
}

export async function deleteScannerDevice(id: ScannerDeviceId): Promise<void> {
	const res = await fetch(scannerDeviceIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export function printerDeviceIri(id: PrinterDeviceId): string {
	return `/api/printer_devices/${id}`;
}

export async function fetchPrinterDevices(): Promise<PrinterDeviceDto[]> {
	const res = await fetch("/api/printer_devices", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<PrinterDeviceDto>(data);
}

export async function fetchPrinterDevice(
	id: PrinterDeviceId,
): Promise<PrinterDeviceDto> {
	const res = await fetch(printerDeviceIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as PrinterDeviceDto;
}

export async function fetchPrinterDrivers(): Promise<PrinterDriverDto[]> {
	const res = await fetch("/api/printer_drivers", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<PrinterDriverDto>(data);
}

export async function fetchPrinterDiscoveryOptions(
	driverCode: string,
): Promise<DiscoveredPrinterOptionDto[]> {
	const q = new URLSearchParams({ driver: driverCode });
	const res = await fetch(`/api/printer_devices/discovery_options?${q}`, {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<DiscoveredPrinterOptionDto>(data);
}

export async function postPrinterDevice(input: {
	driverCode: string;
	connection: Record<string, unknown>;
	name: string;
}): Promise<PrinterDeviceDto> {
	const res = await fetch("/api/printer_devices", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({
			driverCode: input.driverCode,
			connection: input.connection,
			name: input.name,
		}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as PrinterDeviceDto;
}

export async function deletePrinterDevice(id: PrinterDeviceId): Promise<void> {
	const res = await fetch(printerDeviceIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function postPrinterTestPrint(
	id: PrinterDeviceId,
): Promise<{ status: string }> {
	const res = await fetch(`${printerDeviceIri(id)}/test_print`, {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as { status: string };
}

const CATALOG_ITEM_PAGE_SIZE = 100;

async function fetchCatalogItemPage(
	page: number,
	query: URLSearchParams,
	init?: RequestInit,
): Promise<CatalogItemDto[]> {
	const q = new URLSearchParams(query);
	q.set("page", String(page));
	if (!q.has("itemsPerPage")) {
		q.set("itemsPerPage", String(CATALOG_ITEM_PAGE_SIZE));
	}
	if (!q.has("order[name]")) {
		q.set("order[name]", "asc");
	}
	const res = await fetch(`/api/catalog_items?${q}`, {
		headers: { Accept: "application/json" },
		...init,
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<CatalogItemDto>(data);
}

export async function fetchCatalogItemsFlat(
	init?: RequestInit,
): Promise<CatalogItemDto[]> {
	const base = new URLSearchParams();
	base.set("itemsPerPage", String(CATALOG_ITEM_PAGE_SIZE));
	base.set("order[name]", "asc");
	const all: CatalogItemDto[] = [];
	let page = 1;
	while (true) {
		const chunk = await fetchCatalogItemPage(
			page,
			new URLSearchParams(base),
			init,
		);
		all.push(...chunk);
		if (chunk.length < CATALOG_ITEM_PAGE_SIZE) {
			break;
		}
		page += 1;
	}
	return all;
}

export async function searchCatalogItemsPicklist(
	search: string,
	limit = 50,
	init?: RequestInit,
): Promise<CatalogItemDto[]> {
	const q = new URLSearchParams();
	q.set("itemsPerPage", String(limit));
	q.set("page", "1");
	q.set("order[name]", "asc");
	const t = search.trim();
	if (t !== "") {
		q.set("name", t);
	}
	return fetchCatalogItemPage(1, q, init);
}

export async function catalogHasEntries(init?: RequestInit): Promise<boolean> {
	const rows = await searchCatalogItemsPicklist("", 1, init);
	return rows.length > 0;
}

export async function fetchCatalogItems(
	init?: RequestInit,
): Promise<CatalogItemDto[]> {
	return fetchCatalogItemsFlat(init);
}

export async function fetchCatalogItem(
	id: CatalogItemId,
): Promise<CatalogItemDto> {
	const res = await fetch(catalogItemIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as CatalogItemDto;
}

export async function createCatalogItem(input: {
	name: string;
	volume: VolumeDto | null;
	weight: WeightDto | null;
	barcode?: { code: string; type: string };
	catalogItemAttributes?: CatalogItemAttributeWriteRow[];
	linkedPicnicProductId?: string | null;
	creationSource?: "manual" | "picnic" | "fddb";
}): Promise<CatalogItemDto> {
	const body: Record<string, unknown> = {
		name: input.name,
		volume: input.volume,
		weight: input.weight,
	};
	if (input.creationSource !== undefined) {
		body.creationSource = input.creationSource;
	}
	if (input.barcode) {
		body.barcode = input.barcode;
	}
	if (input.catalogItemAttributes !== undefined) {
		body.catalogItemAttributes = input.catalogItemAttributes.map((row) => {
			const out: Record<string, unknown> = {
				attribute: row.attribute,
				value: row.value,
			};
			if (row.id !== undefined) {
				out.id = row.id;
			}
			return out;
		});
	}
	if (input.linkedPicnicProductId !== undefined) {
		body.linkedPicnicProductId = input.linkedPicnicProductId;
	}
	const res = await fetch("/api/catalog_items", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as CatalogItemDto;
}

export async function updateCatalogItem(
	id: CatalogItemId,
	input: {
		name: string;
		volume: VolumeDto | null;
		weight: WeightDto | null;
		barcode?: { code: string; type: string } | null;
		catalogItemAttributes?: CatalogItemAttributeWriteRow[];
		linkedPicnicProductId?: string | null;
	},
): Promise<void> {
	const body: Record<string, unknown> = {
		name: input.name,
		volume: input.volume,
		weight: input.weight,
	};
	if (input.barcode !== undefined) {
		body.barcode = input.barcode;
	}
	if (input.catalogItemAttributes !== undefined) {
		body.catalogItemAttributes = input.catalogItemAttributes.map((row) => {
			const out: Record<string, unknown> = {
				attribute: row.attribute,
				value: row.value,
			};
			if (row.id !== undefined) {
				out.id = row.id;
			}
			return out;
		});
	}
	if (input.linkedPicnicProductId !== undefined) {
		body.linkedPicnicProductId = input.linkedPicnicProductId;
	}
	const res = await fetch(catalogItemIri(id), {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export function cartStockAutomationRulesUrl(
	catalogItemId: CatalogItemId,
): string {
	return `/api/inventory/catalog_items/${catalogItemId}/cart_automation_rules`;
}

export async function fetchCartStockAutomationRules(
	catalogItemId: CatalogItemId,
): Promise<CartStockAutomationRuleDto[]> {
	const res = await fetch(cartStockAutomationRulesUrl(catalogItemId), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<CartStockAutomationRuleDto>(data);
}

export async function createCartStockAutomationRule(input: {
	catalogItemId: CatalogItemId;
	shoppingCartId: ShoppingCartId;
	stockBelow: number;
	addQuantity: number;
	enabled?: boolean;
}): Promise<CartStockAutomationRuleDto> {
	const res = await fetch(cartStockAutomationRulesUrl(input.catalogItemId), {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({
			shoppingCart: shoppingCartIri(input.shoppingCartId),
			stockBelow: input.stockBelow,
			addQuantity: input.addQuantity,
			enabled: input.enabled ?? true,
		}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as CartStockAutomationRuleDto;
}

export async function patchCartStockAutomationRule(
	catalogItemId: CatalogItemId,
	ruleId: CartStockAutomationRuleId,
	patch: {
		shoppingCartId?: ShoppingCartId;
		stockBelow?: number;
		addQuantity?: number;
		enabled?: boolean;
	},
): Promise<void> {
	const body: Record<string, unknown> = {};
	if (patch.shoppingCartId !== undefined) {
		body.shoppingCart = shoppingCartIri(patch.shoppingCartId);
	}
	if (patch.stockBelow !== undefined) {
		body.stockBelow = patch.stockBelow;
	}
	if (patch.addQuantity !== undefined) {
		body.addQuantity = patch.addQuantity;
	}
	if (patch.enabled !== undefined) {
		body.enabled = patch.enabled;
	}
	const res = await fetch(
		`${cartStockAutomationRulesUrl(catalogItemId)}/${ruleId}`,
		{
			method: "PATCH",
			headers: MERGE_PATCH_HEADERS,
			body: JSON.stringify(body),
		},
	);
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteCartStockAutomationRule(
	catalogItemId: CatalogItemId,
	ruleId: CartStockAutomationRuleId,
): Promise<void> {
	const res = await fetch(
		`${cartStockAutomationRulesUrl(catalogItemId)}/${ruleId}`,
		{ method: "DELETE" },
	);
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteCatalogItem(id: CatalogItemId): Promise<void> {
	const res = await fetch(catalogItemIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function fetchInventoryItems(): Promise<InventoryItemDto[]> {
	const res = await fetch("/api/inventory_items", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<InventoryItemDto>(data);
}

export async function fetchInventoryItem(
	id: InventoryItemId,
): Promise<InventoryItemDto> {
	const res = await fetch(inventoryItemIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as InventoryItemDto;
}

export async function createInventoryItem(input: {
	catalogItemId: CatalogItemId;
	locationId: LocationId | null;
	expirationDate: string | null;
}): Promise<void> {
	const res = await fetch("/api/inventory_items", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({
			catalogItem: catalogItemIri(input.catalogItemId),
			...(input.locationId ? { location: locationIri(input.locationId) } : {}),
			...(input.expirationDate
				? { expirationDate: input.expirationDate }
				: { expirationDate: null }),
		}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function updateInventoryItem(
	id: InventoryItemId,
	input: {
		catalogItemId: CatalogItemId;
		locationId: LocationId | null;
		expirationDate: string | null;
	},
): Promise<void> {
	const body: Record<string, unknown> = {
		catalogItem: catalogItemIri(input.catalogItemId),
	};
	if (input.locationId === null) {
		body.location = null;
	} else {
		body.location = locationIri(input.locationId);
	}
	if (input.expirationDate === null) {
		body.expirationDate = null;
	} else {
		body.expirationDate = input.expirationDate;
	}
	const res = await fetch(inventoryItemIri(id), {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteInventoryItem(id: InventoryItemId): Promise<void> {
	const res = await fetch(inventoryItemIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function fetchPicnicIntegrationSettings(): Promise<PicnicIntegrationSettingsDto> {
	const res = await fetch("/api/settings/picnic", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as PicnicIntegrationSettingsDto;
}

export async function fetchPicnicCatalogSearch(
	query: string,
): Promise<PicnicCatalogSearchHitDto[]> {
	const q = new URLSearchParams();
	q.set("query", query.trim());
	const res = await fetch(
		`/api/settings/picnic/catalog-search?${q.toString()}`,
		{
			headers: { Accept: "application/json" },
		},
	);
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<PicnicCatalogSearchHitDto>(data);
}

export async function fetchPicnicCatalogProductSummary(
	productId: string,
): Promise<PicnicCatalogProductSummaryDto> {
	const res = await fetch(
		`/api/catalog_items/picnic_product_hints/${encodeURIComponent(productId)}`,
		{ headers: { Accept: "application/json" } },
	);
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as PicnicCatalogProductSummaryDto;
}

const SHOPPING_CARTS_PROVIDERS_API = "/api/shopping_carts/providers";

export async function fetchCartProviderIndex(): Promise<
	CartProviderIndexEntryDto[]
> {
	const res = await fetch(SHOPPING_CARTS_PROVIDERS_API, {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const body: unknown = await res.json();
	return readJsonArray<CartProviderIndexEntryDto>(body);
}

export async function fetchShoppingCartFromProvider(
	providerId: string,
): Promise<ShoppingCartDto> {
	const res = await fetch(
		`${SHOPPING_CARTS_PROVIDERS_API}/${encodeURIComponent(providerId)}`,
		{
			headers: { Accept: "application/json" },
		},
	);
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ShoppingCartDto;
}

export async function patchPicnicIntegrationSettings(
	patch: Record<string, unknown>,
): Promise<PicnicIntegrationSettingsDto> {
	const res = await fetch("/api/settings/picnic", {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify(patch),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as PicnicIntegrationSettingsDto;
}

export async function picnicLogin(
	body:
		| { username: string; countryCode: string; password?: string }
		| { pendingToken: string; otp: string },
): Promise<PicnicLoginResponse> {
	const res = await fetch("/api/settings/picnic/login", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	const text = await res.text();
	let parsed: unknown;
	try {
		parsed = JSON.parse(text) as unknown;
	} catch {
		if (!res.ok) {
			throw new Error(text || `${res.status} ${res.statusText}`);
		}
		throw new Error("Invalid JSON response");
	}
	const data = parsed as PicnicLoginResponse & { detail?: string };
	if (!res.ok) {
		throw new Error(
			(data.message ?? data.detail ?? text) ||
				`${res.status} ${res.statusText}`,
		);
	}
	return data as PicnicLoginResponse;
}

export function shoppingCartIri(id: ShoppingCartId): string {
	return `/api/shopping_carts/${id}`;
}

export function shoppingCartLineIri(id: ShoppingCartLineId): string {
	return `/api/shopping_cart_lines/${id}`;
}

export async function fetchShoppingCarts(): Promise<ShoppingCartDto[]> {
	const res = await fetch("/api/shopping_carts", {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	const data: unknown = await res.json();
	return readJsonArray<ShoppingCartDto>(data);
}

export async function fetchShoppingCart(
	id: ShoppingCartId,
): Promise<ShoppingCartDto> {
	const res = await fetch(shoppingCartIri(id), {
		headers: { Accept: "application/json" },
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ShoppingCartDto;
}

export async function createShoppingCart(input: {
	name?: string | null;
}): Promise<ShoppingCartDto> {
	const body: Record<string, unknown> = {};
	if (input.name !== undefined) {
		body.name = input.name;
	}
	const res = await fetch("/api/shopping_carts", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ShoppingCartDto;
}

export async function updateShoppingCart(
	id: ShoppingCartId,
	input: { name: string | null },
): Promise<void> {
	const res = await fetch(shoppingCartIri(id), {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify({ name: input.name }),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteShoppingCart(id: ShoppingCartId): Promise<void> {
	const res = await fetch(shoppingCartIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function createShoppingCartLine(input: {
	shoppingCartId: ShoppingCartId;
	catalogItemId: CatalogItemId;
	quantity: number;
}): Promise<ShoppingCartLineDto> {
	const res = await fetch("/api/shopping_cart_lines", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify({
			shoppingCart: shoppingCartIri(input.shoppingCartId),
			catalogItem: catalogItemIri(input.catalogItemId),
			quantity: input.quantity,
		}),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ShoppingCartLineDto;
}

export async function updateShoppingCartLine(
	id: ShoppingCartLineId,
	input: { quantity: number },
): Promise<void> {
	const res = await fetch(shoppingCartLineIri(id), {
		method: "PATCH",
		headers: MERGE_PATCH_HEADERS,
		body: JSON.stringify({ quantity: input.quantity }),
	});
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function deleteShoppingCartLine(
	id: ShoppingCartLineId,
): Promise<void> {
	const res = await fetch(shoppingCartLineIri(id), { method: "DELETE" });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
}

export async function picnicRequestTwoFactorCode(body: {
	pendingToken: string;
	channel?: "SMS" | "EMAIL";
}): Promise<PicnicRequestTwoFactorCodeResponse> {
	const res = await fetch("/api/settings/picnic/login/request_2fa_code", {
		method: "POST",
		headers: JSON_HEADERS,
		body: JSON.stringify(body),
	});
	const text = await res.text();
	let parsed: unknown;
	try {
		parsed = JSON.parse(text) as unknown;
	} catch {
		if (!res.ok) {
			throw new Error(text || `${res.status} ${res.statusText}`);
		}
		throw new Error("Invalid JSON response");
	}
	const data = parsed as PicnicRequestTwoFactorCodeResponse & {
		detail?: string;
	};
	if (!res.ok) {
		throw new Error(
			(data.message ?? data.detail ?? text) ||
				`${res.status} ${res.statusText}`,
		);
	}
	return data;
}

export async function fetchActivity(): Promise<ActivityListDto> {
	const res = await fetch("/api/activity", { headers: { Accept: "application/json" } });
	if (!res.ok) {
		throw new Error(await readErrorMessage(res));
	}
	return (await res.json()) as ActivityListDto;
}
