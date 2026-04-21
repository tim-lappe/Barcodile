import {
	type ReactNode,
	useCallback,
	useEffect,
	useMemo,
	useState,
} from "react";
import { fetchPicnicIntegrationSettings } from "../api/barcodileClient";
import type { PicnicIntegrationSettingsDto } from "../domain/barcodile";
import { PicnicConnectionContext } from "./picnicConnectionContext";

export function PicnicConnectionProvider({
	children,
}: {
	children: ReactNode;
}) {
	const [picnicConnected, setPicnicConnected] = useState(false);
	const [picnicStatusLoading, setPicnicStatusLoading] = useState(true);

	const refreshPicnicConnectionStatus = useCallback(async () => {
		setPicnicStatusLoading(true);
		try {
			const dto = await fetchPicnicIntegrationSettings();
			setPicnicConnected(dto.hasStoredAuthSession);
		} catch {
			setPicnicConnected(false);
		} finally {
			setPicnicStatusLoading(false);
		}
	}, []);

	const notifyPicnicSessionFromDto = useCallback(
		(dto: PicnicIntegrationSettingsDto) => {
			setPicnicConnected(dto.hasStoredAuthSession);
		},
		[],
	);

	useEffect(() => {
		void refreshPicnicConnectionStatus();
	}, [refreshPicnicConnectionStatus]);

	const value = useMemo(
		() => ({
			picnicConnected,
			picnicStatusLoading,
			refreshPicnicConnectionStatus,
			notifyPicnicSessionFromDto,
		}),
		[
			picnicConnected,
			picnicStatusLoading,
			refreshPicnicConnectionStatus,
			notifyPicnicSessionFromDto,
		],
	);

	return (
		<PicnicConnectionContext.Provider value={value}>
			{children}
		</PicnicConnectionContext.Provider>
	);
}
