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
			tileDescription:
				"Enter name, sizing, attributes, and an optional barcode yourself.",
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
				"Enter a barcode: the app looks up the product on the web and creates the catalog item.",
			formTitle: "New catalog item from Barcode",
			formSubtitle:
				"This path is started from the catalog list dialog; you normally do not open this URL directly.",
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
	if (raw === "picnic" || raw === "manual") {
		return raw;
	}
	return "manual";
}

export function catalogItemNewPath(source: "manual" | "picnic"): string {
	const q = new URLSearchParams({
		[CATALOG_ITEM_CREATION_SOURCE_QUERY]: source,
	});
	return `/catalog-items/new?${q.toString()}`;
}
