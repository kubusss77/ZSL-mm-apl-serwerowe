import {CardData, Player, WinnerData} from '../GameplayTypes.js';
import {normalizeColor} from '../utils.js';
import {API_ENDPOINTS} from '../constants.js';

export interface GameStateResponse {
    myCards: CardData[];
    mySecretColor: string;
    boardState: string[][];
    winner: WinnerData | null;
    currentTurn: number;
    isMyTurn: boolean;
    players: Player[];
    cardsPlayedCount: number;
    lastPlayedCard: CardData | null;
    turnDeadline: number;
    botReadyAt: number;
}

const FETCH_TIMEOUT_MS = 8000;

/** Wykonuje fetch z limitem czasu, żeby UI nie wisiał na żądaniu. */
async function fetchWithTimeout(input: RequestInfo, init?: RequestInit): Promise<Response> {
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), FETCH_TIMEOUT_MS);
    try {
        return await fetch(input, {...init, signal: controller.signal});
    } finally {
        window.clearTimeout(timeoutId);
    }
}

/** Pobiera i normalizuje stan gry dla aktualnego gracza. */
export async function fetchGameState(
    roomId: number,
    playerId: number,
    token: string
): Promise<GameStateResponse | null> {
    try {
        const res = await fetchWithTimeout(
            `${API_ENDPOINTS.GAME}?action=getState&room_id=${roomId}&player_id=${playerId}&token=${encodeURIComponent(token)}&_=${Date.now()}`
        );
        const data = await res.json();

        if (!data || data.status === 'error') return null;

        const gs = data.game_state;
        if (!gs) return null;

        const currentTurn = parseInt(gs.current_turn, 10);
        const me = data.players[playerId];

        const players: Player[] = Object.keys(data.players).map((id) => ({
            id: parseInt(id, 10),
            ...data.players[id],
        }));

        let myCards: CardData[] = [];
        let mySecretColor = 'card_back';

        if (me) {
            myCards = me.cards ?? [];
            if (me.color) {
                mySecretColor = `turtle_token_${normalizeColor(me.color)}`;
            }
        }

        return {
            myCards,
            mySecretColor,
            boardState: gs.board,
            winner: gs.winner,
            currentTurn,
            isMyTurn: currentTurn === playerId,
            players,
            cardsPlayedCount: gs.cards_played_count ?? 0,
            lastPlayedCard: gs.last_played_card ?? null,
            turnDeadline: gs.turn_deadline ?? 0,
            botReadyAt: gs.bot_ready_at ?? 0,
        };
    } catch (e) {
        console.error('Game state sync error:', e);
        return null;
    }
}

/** Wysyła zagranie karty do serwera. */
export async function playCard(
    roomId: number,
    playerId: number,
    token: string,
    cardId: string,
    chosenColor: string
): Promise<{ success: boolean; message?: string }> {
    try {
        const body = new FormData();
        body.append('action', 'playCard');
        body.append('room_id', roomId.toString());
        body.append('player_id', playerId.toString());
        body.append('token', token);
        body.append('card_id', cardId);
        body.append('chosen_color', chosenColor);

        const res = await fetchWithTimeout(API_ENDPOINTS.GAME, {method: 'POST', body});
        const data = await res.json();

        return {success: data.status === 'success', message: data.message};
    } catch (e) {
        console.error('Play card error:', e);
        return {success: false, message: 'Timeout serwera — spróbuj ponownie'};
    }
}
