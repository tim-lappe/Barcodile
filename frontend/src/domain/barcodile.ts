export type LocationId = string;

export type CatalogItemId = string;

export type InventoryItemId = string;

export type ShoppingCartId = string;

export type ShoppingCartLineId = string;

export type CartStockAutomationRuleId = string;

export type CartStockAutomationRuleDto = {
	id: CartStockAutomationRuleId;
	catalogItem: string;
	shoppingCart: string;
	stockBelow: number;
	addQuantity: number;
	enabled: boolean;
	createdAt: string;
};

export type ShoppingCartLineDto = {
	id: ShoppingCartLineId;
	catalogItem: CatalogItemDto;
	quantity: number;
	createdAt: string;
};

export type ShoppingCartDto = {
	id: ShoppingCartId;
	name: string | null;
	createdAt: string;
	lines?: ShoppingCartLineDto[];
};

export type CartProviderIndexEntryDto = {
	id: string;
	name: string;
	lineCount: number;
	createdAt: string;
};

const STORED_SHOPPING_CART_ID_RE =
	/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

export function isStoredShoppingCartId(id: string): boolean {
	return STORED_SHOPPING_CART_ID_RE.test(id);
}

export type CatalogItemAttributeId = string;

export type VolumeUnit = "ml" | "l";

export type WeightUnit = "g" | "kg";

export const CATALOG_ITEM_ATTRIBUTE_OPTIONS = [
	{ value: "alcohol_percent" as const, label: "Alcohol %" },
] as const;

export type CatalogItemAttributeKey =
	(typeof CATALOG_ITEM_ATTRIBUTE_OPTIONS)[number]["value"];

export type ScannerDeviceId = string;

export type ScannerDeviceDto = {
	id: ScannerDeviceId;
	deviceIdentifier: string;
	name: string;
	lastScannedCodes: string[];
	automationAddInventoryOnEanScan: boolean;
	automationCreateCatalogItemIfMissingForEan: boolean;
	automationRemoveInventoryOnPublicCodeScan: boolean;
};

export type InputDeviceOptionDto = {
	deviceIdentifier: string;
	label: string;
};

export type PrinterDeviceId = string;

export type PrinterDeviceDto = {
	id: PrinterDeviceId;
	driverCode: string;
	connection: Record<string, unknown>;
	printSettings: Record<string, unknown>;
	name: string;
};

export type PrinterLabelSizeOptionDto = {
	value: string;
	label: string;
};

export type PrinterColorModeOptionDto = {
	value: string;
	label: string;
	red: boolean;
};

export type PrinterPrintSettingOptionsDto = {
	labelSizes: PrinterLabelSizeOptionDto[];
	colorModes: PrinterColorModeOptionDto[];
};

export type PrinterDriverDto = {
	code: string;
	label: string;
	defaultPrintSettings: Record<string, unknown>;
	printSettingOptions: PrinterPrintSettingOptionsDto;
};

export type DiscoveredPrinterOptionDto = {
	deviceIdentifier: string;
	label: string;
	suggestedConnection: Record<string, string>;
	suggestedPrintSettings: Record<string, unknown>;
};

export type LocationDto = {
	id: LocationId;
	name: string;
	parent?: LocationDto | string | null;
};

export function parentIdOf(loc: LocationDto): LocationId | null {
	const p = loc.parent;
	if (p == null) {
		return null;
	}
	if (typeof p === "string") {
		const m = p.match(/\/api\/locations\/([^/?#]+)/);
		return m?.[1] ?? null;
	}
	return p.id;
}

export function childrenByParentId(
	all: LocationDto[],
): Map<LocationId | null, LocationId[]> {
	const m = new Map<LocationId | null, LocationId[]>();
	for (const loc of all) {
		const p = parentIdOf(loc);
		const list = m.get(p) ?? [];
		list.push(loc.id);
		m.set(p, list);
	}
	return m;
}

export function forbiddenParentIdsForEdit(
	all: LocationDto[],
	selfId: LocationId,
): Set<LocationId> {
	const children = childrenByParentId(all);
	const out = new Set<LocationId>([selfId]);
	const stack = [...(children.get(selfId) ?? [])];
	while (stack.length > 0) {
		const id = stack.pop();
		if (id === undefined) {
			continue;
		}
		if (out.has(id)) {
			continue;
		}
		out.add(id);
		stack.push(...(children.get(id) ?? []));
	}
	return out;
}

export type BarcodeDto = {
	code: string;
	type: string;
};

export type CatalogItemAttributeDto = {
	id: CatalogItemAttributeId;
	attribute: CatalogItemAttributeKey;
	value: unknown | null;
};

export type VolumeDto = {
	amount: string;
	unit: VolumeUnit;
};

export type WeightDto = {
	amount: string;
	unit: WeightUnit;
};

export type CatalogItemDto = {
	id: CatalogItemId;
	name: string;
	imageFileName?: string | null;
	volume?: VolumeDto | null;
	weight?: WeightDto | null;
	barcode?: BarcodeDto | null;
	catalogItemAttributes?: CatalogItemAttributeDto[];
	linkedPicnicProductId?: string | null;
};

export type InventoryItemDto = {
	id: InventoryItemId;
	publicCode: string;
	catalogItem: CatalogItemDto;
	location: LocationDto | null;
	expirationDate?: string | null;
	createdAt: string;
};

export type InventoryItemLabelPrintResponse = {
	status: string;
};

export function firstBarcodeCode(type: {
	barcode?: BarcodeDto | null;
}): string {
	return type.barcode?.code ?? "";
}

export function catalogItemPickerLabel(type: CatalogItemDto): string {
	const code = firstBarcodeCode(type);
	return code ? `${type.name} (${code})` : type.name;
}

export function catalogItemAttributeLabel(
	key: CatalogItemAttributeKey,
): string {
	const opt = CATALOG_ITEM_ATTRIBUTE_OPTIONS.find((o) => o.value === key);
	return opt ? opt.label : key;
}

export function formatCatalogItemAttributeSummary(
	attribute: CatalogItemAttributeKey,
	value: unknown | null | undefined,
): string {
	const label = catalogItemAttributeLabel(attribute);
	if (value === null || value === undefined || value === "") {
		return label;
	}
	if (attribute === "alcohol_percent") {
		return `${label} ${String(value)}%`;
	}
	if (typeof value === "object") {
		return `${label}: ${JSON.stringify(value)}`;
	}
	return `${label}: ${String(value)}`;
}

export function formatVolumeShort(v: VolumeDto | null | undefined): string {
	if (!v) {
		return "";
	}
	return `${v.amount} ${v.unit}`;
}

export function formatWeightShort(w: WeightDto | null | undefined): string {
	if (!w) {
		return "";
	}
	return `${w.amount} ${w.unit}`;
}

export type PicnicCountryCode = "NL" | "DE";

export type PicnicIntegrationSettingsDto = {
	id: string;
	username: string | null;
	countryCode: PicnicCountryCode;
	hasStoredPassword: boolean;
	hasStoredAuthSession: boolean;
};

export type PicnicCatalogSearchHitDto = {
	id: string;
	name: string;
	imageId: string | null;
	displayPrice: number | null;
	unitQuantity: string | null;
};

export type PicnicCatalogProductSummaryDto = {
	id: string;
	name: string;
	brand: string;
	unitQuantity: string;
	volume: VolumeDto | null;
	weight: WeightDto | null;
	eanBarcode: string | null;
};

export type PicnicRequestTwoFactorCodeResponse = {
	ok: boolean;
	message: string;
};

export type PicnicLoginResponse =
	| {
			ok: true;
			secondFactorAuthenticationRequired: true;
			pendingToken: string;
			message: string;
	  }
	| {
			ok: true;
			secondFactorAuthenticationRequired: false;
			message: string;
	  }
	| {
			ok: false;
			message: string;
	  };

export type PersistedDomainEventItemDto = {
	id: string;
	eventClass: string;
	data: unknown;
	createdAt: string;
};

export type ActivityListDto = {
	items: PersistedDomainEventItemDto[];
};
