export type CatalogItemCreationSourceId = "manual" | "picnic" | "barcode";

export type CatalogItemCreationSourceDef = {
	id: CatalogItemCreationSourceId;
	tileTitle: string;
	tileDescription: string;
	formTitle: string;
	formSubtitle: string;
};

export const CATALOG_ITEM_CREATION_SOURCE_QUERY = "source";

export const CATALOG_ITEM_PICNIC_PRODUCT_QUERY = "picnicProduct";

export const CATALOG_ITEM_BARCODE_QUERY = "barcode";

export function catalogItemNewPathWithPicnicProduct(productId: string): string {
	const q = new URLSearchParams();
	q.set(CATALOG_ITEM_PICNIC_PRODUCT_QUERY, productId);
	return `/catalog-items/new?${q.toString()}`;
}

export const CATALOG_ITEM_CREATION_SOURCES: readonly CatalogItemCreationSourceDef[] =
	[
		{
			id: "manual",
			tileTitle: "Create manually",
			tileDescription: "Enter name, sizing, attributes, and an optional barcode yourself.",
			formTitle: "New catalog item",
			formSubtitle:
				"Define how items of this product are described before you add inventory rows.",
		},
		{
			id: "picnic",
			tileTitle: "Create from Picnic",
			tileDescription:
				"Start from a Picnic catalog product id and align defaults with the retailer.",
			formTitle: "New catalog item from Picnic",
			formSubtitle:
				"Link a Picnic product id and adjust volume, weight, and attributes so stock and carts stay consistent.",
		},
		{
			id: "barcode",
			tileTitle: "Create from Barcode",
			tileDescription:
				"Look up product details from a configured barcode database (for example BarcodeLookup.com) using the article number.",
			formTitle: "New catalog item from barcode",
			formSubtitle:
				"Enter a barcode, use Look up product to prefill the form, then adjust sizing and attributes before saving.",
		},
	] as const;

export function catalogItemCreationSourceDef(
	id: CatalogItemCreationSourceId,
): CatalogItemCreationSourceDef {
	const found = CATALOG_ITEM_CREATION_SOURCES.find((s) => s.id === id);
	if (!found) {
		return CATALOG_ITEM_CREATION_SOURCES[0];
	}
	return found;
}

export function parseCatalogItemCreationSource(
	raw: string | null | undefined,
): CatalogItemCreationSourceId {
	if (raw === "picnic" || raw === "barcode" || raw === "manual") {
		return raw;
	}
	return "manual";
}

export function catalogItemNewPath(
	source: CatalogItemCreationSourceId,
): string {
	const q = new URLSearchParams({
		[CATALOG_ITEM_CREATION_SOURCE_QUERY]: source,
	});
	return `/catalog-items/new?${q.toString()}`;
}
