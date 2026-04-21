import { useContext } from "react";
import {
	PicnicConnectionContext,
	type PicnicConnectionContextValue,
} from "./picnicConnectionContext";

export function usePicnicConnection(): PicnicConnectionContextValue {
	const ctx = useContext(PicnicConnectionContext);
	if (!ctx) {
		throw new Error(
			"usePicnicConnection must be used within PicnicConnectionProvider",
		);
	}
	return ctx;
}
