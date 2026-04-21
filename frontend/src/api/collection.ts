export function readJsonArray<T>(data: unknown): T[] {
	if (Array.isArray(data)) {
		return data as T[];
	}
	if (data && typeof data === "object") {
		const o = data as Record<string, unknown>;
		const member = o["hydra:member"] ?? o.member;
		if (Array.isArray(member)) {
			return member as T[];
		}
	}
	return [];
}
