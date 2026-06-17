import {GameScene} from './types.js';
import {NicknameScene} from './NicknameScene.js';
import {
    BOARD,
    FIELD_MAP,
    HUD,
    POLL_INTERVAL_MS,
    SESSION_KEY,
} from './constants.js';
import {CardData, ChatMessage, Player, TurtlePosition, WinnerData} from './GameplayTypes.js';
import {fetchGameState, playCard} from './api/GameApi.js';
import {pollChat, sendChatMessage} from './api/ChatApi.js';
import {calibrateFieldPositions, getBoardDimensions, renderBoard, renderVignette} from './renderers/BoardRenderer.js';
import {renderTurtles} from './renderers/TurtleRenderer.js';
import {renderCardsFan, renderSecretCard} from './renderers/CardRenderer.js';
import {HudExitBtn, renderHUD, renderPlayerBar} from './renderers/HudRenderer.js';
import {isChatArea, renderChat} from './renderers/ChatRenderer.js';
import {ColorModalResult, renderColorSelectionModal} from './renderers/ColorModalRenderer.js';

export class GameplayScene extends GameScene {
    private myCards: CardData[] = [];
    private boardState: string[][] = [];
    private isMyTurn = false;
    private winner: WinnerData | null = null;
    private mySecretColor = 'card_back';
    private turtlePositions: Record<string, TurtlePosition> = {};
    private cardsPlayedCount = 0;
    private lastPlayedCard: CardData | null = null;
    private turnDeadline = 0;
    private timeLeft = 0;
    private isReady = false;
    private mouseX = 0;
    private mouseY = 0;
    private hoveredCardIndex = -1;
    private exitBtn: HudExitBtn = {x: 20, y: 60};
    private chatMessages: ChatMessage[] = [];
    private lastMsgId = 0;
    private currentChatInput = '';
    private isChatActive = false;
    private activeColorSelectionCardIndex: number | null = null;
    private colorModalResult: ColorModalResult = {regions: [], cancelPath: new Path2D()};
    private unifiedFieldPositions: [number, number][] = [];
    private updateInterval!: number;
    private players: Player[] = [];
    private currentTurn = 0;
    private botReadyAt = 0;
    private syncInFlight = false;
    private playInFlight = false;
    private cardsPlayedCountSeen = 0;
    private chatAbortController: AbortController | null = null;

    /** Pobiera pierwszy stan gry, uruchamia polling gry i czatu. */
    public async onInit() {
        await this.syncGameState();
        this.updateInterval = window.setInterval(() => this.syncGameState(), POLL_INTERVAL_MS);
        this.chatAbortController = new AbortController();
        pollChat(this.context.roomId, () => this.lastMsgId, (msgs, lastId) => {
            const known = new Set(this.chatMessages.map(m => m.id));
            const fresh = msgs.filter(m => !known.has(m.id));
            if (fresh.length === 0) return;
            this.chatMessages = [...this.chatMessages, ...fresh].slice(-12);
            this.lastMsgId = lastId;
        }, this.chatAbortController.signal);
        this.recalibrate();
        this.isReady = true;
    }

    /** Synchronizuje lokalny stan sceny ze stanem serwera. */
    private async syncGameState() {
        if (this.syncInFlight) {
            return;
        }
        this.syncInFlight = true;
        try {
            const state = await fetchGameState(
                this.context.roomId,
                this.context.playerId,
                this.context.token
            );
            if (!state) return;

            const boardChanged = state.cardsPlayedCount !== this.cardsPlayedCountSeen;
            Object.assign(this, state);
            if (boardChanged) {
                this.cardsPlayedCountSeen = state.cardsPlayedCount;
            }
            this.recalibrate();
        } finally {
            this.syncInFlight = false;
        }
    }

    /** Przelicza pozycje pól planszy dla aktualnego rozmiaru canvasa. */
    private recalibrate() {
        const {width: w, height: h} = this.context.canvas;
        this.unifiedFieldPositions = calibrateFieldPositions(w, h, FIELD_MAP);
    }

    /** Obsługuje kliknięcie karty i ewentualny wybór koloru. */
    private handleCardClick(cardIndex: number) {
        if (this.playInFlight || !this.isMyTurn || this.winner) {
            return;
        }
        const card = this.myCards[cardIndex];
        const isMultiColorNonLF = card.color === 'any' && !String(card.move ?? '').includes('LF');
        if (isMultiColorNonLF) {
            this.activeColorSelectionCardIndex = cardIndex;
        } else {
            const chosenColor = String(card.move ?? '').includes('LF') ? '' : card.color;
            this.executePlay(cardIndex, chosenColor);
        }
    }

    /** Wysyła wybrane zagranie do serwera i odświeża stan gry. */
    private async executePlay(cardIndex: number, chosenColor: string) {
        if (this.playInFlight) return;
        this.playInFlight = true;

        const card = this.myCards[cardIndex];
        try {
            const result = await playCard(
                this.context.roomId,
                this.context.playerId,
                this.context.token,
                card.id,
                chosenColor
            );
            if (result.success) {
                this.activeColorSelectionCardIndex = null;
                await this.syncGameState();
            } else if (result.message) {
                alert(result.message);
            }
        } finally {
            this.playInFlight = false;
        }
    }

    /** Aktualizuje licznik tury oraz animowane pozycje żółwi. */
    public onUpdate(): void {
        if (this.turnDeadline > 0 && !this.winner) {
            this.timeLeft = Math.max(0, this.turnDeadline - Math.floor(Date.now() / 1000));
        }

        this.boardState.forEach((stack, fieldIdx) => {
            const target = this.unifiedFieldPositions[fieldIdx];
            if (!target) return;
            const {drawW} = getBoardDimensions(this.context.canvas.width, this.context.canvas.height);
            const startGap = BOARD.START_ROW_GAP * (drawW / BOARD.BASE_SCALE_REF_WIDTH);
            const startRowOffset = ((stack.length - 1) * startGap) / 2;

            stack.forEach((color, stackIdx) => {
                const targetX = fieldIdx === 0
                    ? target[0] + stackIdx * startGap - startRowOffset
                    : target[0];
                const targetY = target[1];

                if (!this.turtlePositions[color]) {
                    this.turtlePositions[color] = {x: targetX, y: targetY};
                }
                this.turtlePositions[color].x += (targetX - this.turtlePositions[color].x) * BOARD.LERP_FACTOR;
                this.turtlePositions[color].y += (targetY - this.turtlePositions[color].y) * BOARD.LERP_FACTOR;
            });
        });
    }

    /** Rysuje pełny widok rozgrywki. */
    public onRender() {
        const ctx = this.context.ctx;
        const {spritesheet: img, spritesData: sprites, board} = this.context.assets;
        const {width: cW, height: cH} = this.context.canvas;

        renderBoard(ctx, board ?? null, cW, cH);
        renderVignette(ctx, cW, cH);
        if (!this.isReady) return;

        const {drawW} = getBoardDimensions(cW, cH);

        renderTurtles(
            ctx, img!, sprites!, this.boardState, this.turtlePositions,
            this.unifiedFieldPositions, drawW, this.mouseX, this.mouseY,
            this.activeColorSelectionCardIndex !== null
        );

        renderSecretCard(ctx, img!, sprites!, cW, this.mySecretColor, this.mouseX, this.mouseY);

        this.hoveredCardIndex = renderCardsFan(
            ctx, img!, sprites!, this.myCards, cW, cH,
            this.mouseX, this.mouseY, this.activeColorSelectionCardIndex !== null
        );

        renderChat(ctx, cH, this.chatMessages, this.currentChatInput, this.isChatActive);

        renderPlayerBar(ctx, this.players, this.currentTurn, this.context.playerId, this.timeLeft, this.winner);

        this.exitBtn = renderHUD(
            ctx, img!, sprites!, this.isMyTurn, this.timeLeft, this.winner,
            this.cardsPlayedCount, this.lastPlayedCard, this.mouseX, this.mouseY
        );

        if (this.activeColorSelectionCardIndex !== null) {
            this.colorModalResult = renderColorSelectionModal(
                ctx, img!, sprites!, cW, cH, this.mouseX, this.mouseY
            );
        }
    }

    /** Obsługuje kliknięcia w modal koloru, HUD, czat i karty. */
    public onMouseDown(mx: number, my: number) {
        const ctx = this.context.ctx;

        if (this.activeColorSelectionCardIndex !== null) {
            if (ctx.isPointInPath(this.colorModalResult.cancelPath, mx, my)) {
                this.activeColorSelectionCardIndex = null;
                return;
            }
            for (const region of this.colorModalResult.regions) {
                if (ctx.isPointInPath(region.path, mx, my)) {
                    this.executePlay(this.activeColorSelectionCardIndex, region.color);
                    return;
                }
            }
            const modalX = (this.context.canvas.width - 500) / 2;
            const modalY = (this.context.canvas.height - 200) / 2;
            if (mx < modalX || mx > modalX + 500 || my < modalY || my > modalY + 200) {
                this.activeColorSelectionCardIndex = null;
            }
            return;
        }

        if (this.winner) {
            const {x, y} = this.exitBtn;
            if (mx >= x && mx <= x + HUD.EXIT_BTN_W && my >= y && my <= y + HUD.EXIT_BTN_H) {
                localStorage.removeItem(SESSION_KEY);
                this.context.changeScene(new NicknameScene());
                return;
            }
        }

        if (isChatArea(mx, my, this.context.canvas.height)) {
            this.isChatActive = true;
            return;
        }

        this.isChatActive = false;

        if (this.hoveredCardIndex !== -1) {
            this.handleCardClick(this.hoveredCardIndex);
        }
    }

    /** Zapamiętuje aktualną pozycję myszy dla hoverów. */
    public onMouseMove(mx: number, my: number) {
        this.mouseX = mx;
        this.mouseY = my;
    }

    /** Obsługuje pisanie na czacie i aktywację pola Enterem. */
    public onKeyDown(e: KeyboardEvent) {
        if (e.key === 'Enter') {
            if (this.isChatActive) {
                void this.submitChatMessage();
            } else {
                this.isChatActive = true;
            }
            return;
        }

        if (this.isChatActive) {
            if (e.key === 'Backspace') {
                this.currentChatInput = this.currentChatInput.slice(0, -1);
            } else if (e.key.length === 1 && this.currentChatInput.length < 40) {
                this.currentChatInput += e.key;
            }
            e.preventDefault();
        }
    }

    /** Zatrzymuje cykliczną synchronizację stanu gry. */
    public onDestroy() {
        clearInterval(this.updateInterval);
        this.chatAbortController?.abort();
        this.chatAbortController = null;
    }

    /** Wysyła aktualnie wpisaną wiadomość czatu. */
    private async submitChatMessage(): Promise<void> {
        const text = this.currentChatInput.trim();
        if (!text) return;

        const sent = await sendChatMessage(this.context.roomId, this.context.nickname, text);
        this.currentChatInput = '';
        this.isChatActive = false;

        if (sent) {
            this.chatMessages = [...this.chatMessages, sent].slice(-12);
            this.lastMsgId = Math.max(this.lastMsgId, sent.id);
        }
    }

    /** Reaguje na zmianę rozmiaru canvasa przez przeliczenie planszy. */
    public onResize(): void {
        this.recalibrate();
    }
}
