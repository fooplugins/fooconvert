export const rootClass = 'fc-ai-popup-builder';

export const config = window.FC_AI_POPUP_BUILDER || {};

export const debugTabAvailable = Boolean(
	config?.debug?.enabled && config?.debug?.canManage
);
