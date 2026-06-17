import {ChatMessage} from '../GameplayTypes.js';
import {API_ENDPOINTS} from '../constants.js';

const LONG_POLL_TIMEOUT_S = 30;

export type OnMessagesCallback = (messages: ChatMessage[], lastId: number) => void;

/** Uruchamia pętlę long-pollingu czatu dla pokoju gry. */
export async function pollChat(
    gameId: number,
    getLastId: () => number,
    onMessages: OnMessagesCallback,
    signal?: AbortSignal
): Promise<void> {
    const loop = async () => {
        if (signal?.aborted) return;
        try {
            const controller = new AbortController();
            const combinedSignal = signal
                ? AbortSignal.any([signal, controller.signal])
                : controller.signal;

            const clientTimeout = setTimeout(
                () => controller.abort(),
                (LONG_POLL_TIMEOUT_S + 5) * 1000
            );

            const res = await fetch(
                `${API_ENDPOINTS.CHAT_POLL}?game_id=${gameId}&last_id=${getLastId()}&timeout=${LONG_POLL_TIMEOUT_S}`,
                {signal: combinedSignal}
            );
            clearTimeout(clientTimeout);

            const data: ChatMessage[] = await res.json();
            if (data?.length > 0) {
                onMessages(data, data[data.length - 1].id);
            }
        } catch (e: any) {
            if (e?.name === 'AbortError' && signal?.aborted) return;
            console.error('Chat poll error:', e);
            await new Promise(r => setTimeout(r, 2000));
        }
        loop();
    };
    loop();
}

/** Wysyła wiadomość czatu i zwraca zapisaną wiadomość serwera. */
export async function sendChatMessage(
    gameId: number,
    user: string,
    text: string
): Promise<ChatMessage | null> {
    if (!text.trim()) return null;
    try {
        const body = new FormData();
        body.append('game_id', gameId.toString());
        body.append('user', user);
        body.append('text', text.trim());
        const res = await fetch(API_ENDPOINTS.CHAT_SEND, {method: 'POST', body});
        const data = await res.json();
        return data.status === 'success' ? data.message : null;
    } catch (e) {
        console.error('Chat send error:', e);
        return null;
    }
}
