import { createContext } from "react";
import type { PicnicIntegrationSettingsDto } from "../domain/barcodile";

export type PicnicConnectionContextValue = {
	picnicConnected: boolean;
	picnicStatusLoading: boolean;
	refreshPicnicConnectionStatus: () => Promise<void>;
	notifyPicnicSessionFromDto: (dto: PicnicIntegrationSettingsDto) => void;
};

export const PicnicConnectionContext =
	createContext<PicnicConnectionContextValue | null>(null);
