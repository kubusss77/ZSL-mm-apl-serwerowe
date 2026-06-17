import {API_ENDPOINTS, SESSION_KEY} from '../constants.js';

export interface SavedSession {
    roomId: number;
    playerId: number;
    token: string;
    nickname: string;
}

export type ReconnectStatus = 'playing' | 'lobby' | 'expired';

/** Odczytuje zapisaną sesję gracza z localStorage. */
export function loadSession(): SavedSession | null {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as SavedSession;
    } catch {
        return null;
    }
}

/** Czyści zapisaną sesję gracza. */
export function clearSession(): void {
    localStorage.removeItem(SESSION_KEY);
}

/** Sprawdza na serwerze, czy zapisana sesja nadal jest ważna. */
export async function reconnect(
    session: SavedSession
): Promise<{ status: ReconnectStatus } | null> {
    try {
        const {roomId, playerId, token} = session;
        const res = await fetch(
            `${API_ENDPOINTS.GAME}?action=reconnect&room_id=${roomId}&player_id=${playerId}&token=${encodeURIComponent(token)}`
        );
        const data = await res.json();

        if (data.status !== 'success') return null;
        return {status: data.gameStatus === 'playing' ? 'playing' : 'lobby'};
    } catch {
        return null;
    }
}
