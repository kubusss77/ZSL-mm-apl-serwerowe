import {GameScene, Player, RoomState} from './types.js';
import {GameplayScene} from './GameplayScene.js';
import {text} from './SceneHelper.js';
import {drawRoundedRect} from "./utils.js";
import {API_ENDPOINTS} from './constants.js';

const TILE_W = 120;
const TILE_H = 46;
const TILE_GAP = 20;
const TILE_RADIUS = 22;
const PLAYER_COUNT = 5;

const SWITCH_W = 74;
const SWITCH_H = 36;
const SWITCH_RADIUS = 18;
const SWITCH_KNOB_RADIUS = 14;
const SWITCH_KNOB_INSET = 19;
const SWITCH_LABEL_OFFSET_Y = 28;

const BOT_BTN_W = 220;
const BOT_BTN_H = 40;
const BOT_BTN_RADIUS = 20;
const BOT_BTN_GAP_Y = 16;

const LABEL_BELOW_TILES_OFFSET_Y = 20;

const POLL_INTERVAL_MS = 400;

export class LobbyScene extends GameScene {
    private playersList: Record<string, Player> = {};
    private amIReady = false;
    private pollingInterval!: number;

    private startX = 0;
    private tileY = 0;
    private switchX = 0;
    private switchY = 0;
    private botBtnX = 0;
    private botBtnY = 0;
    private addingBot = false;

    /** Uruchamia cykliczne odświeżanie stanu lobby. */
    public onInit() {
        this.onResize(this.context.canvas.width, this.context.canvas.height);
        this.pollingInterval = window.setInterval(() => this.fetchLobbyState(), POLL_INTERVAL_MS);
        this.fetchLobbyState();
    }

    /** Przelicza pozycje kafelków graczy i kontrolek lobby. */
    public onResize(width: number, height: number) {
        const totalW = PLAYER_COUNT * TILE_W + (PLAYER_COUNT - 1) * TILE_GAP;
        this.startX = (width - totalW) / 2;
        this.tileY = height / 2 - TILE_H / 2;
        this.switchX = (width - SWITCH_W) / 2;
        this.switchY = this.tileY + TILE_H + LABEL_BELOW_TILES_OFFSET_Y + 30;
        this.botBtnX = (width - BOT_BTN_W) / 2;
        this.botBtnY = this.switchY + SWITCH_H + 50;
    }

    /** Pobiera aktualny stan lobby i przełącza do gry po starcie. */
    private async fetchLobbyState() {
        try {
            const res = await fetch(`${API_ENDPOINTS.GAME}?action=getState&room_id=${this.context.roomId}`);
            const data: RoomState = await res.json();
            if (!data) return;

            this.playersList = data.players ?? {};

            const me = data.players?.[this.context.playerId];
            if (me) this.amIReady = me.is_ready;

            if (data.status === 'playing') {
                this.context.changeScene(new GameplayScene());
            }
        } catch (e) {
            console.error('Lobby sync error:', e);
        }
    }

    /** Nie wykonuje dodatkowej logiki w pętli animacji. */
    public onUpdate() {
    }

    /** Rysuje listę graczy, przełącznik gotowości i przycisk bota. */
    public onRender() {
        const ctx = this.context.ctx;

        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, this.context.canvas.width, this.context.canvas.height);

        this.renderPlayerTiles(ctx);
        this.renderReadySwitch(ctx);
        this.renderAddBotButton(ctx);
    }

    /** Rysuje pięć miejsc dla graczy w lobby. */
    private renderPlayerTiles(ctx: CanvasRenderingContext2D) {
        for (let i = 1; i <= PLAYER_COUNT; i++) {
            const player: Player | undefined = this.playersList[i.toString()];
            const x = this.startX + (i - 1) * (TILE_W + TILE_GAP);
            const isMe = i === this.context.playerId;

            ctx.fillStyle = player
                ? (player.is_bot
                    ? '#1a4a8a'
                    : (player.is_ready ? '#008000' : '#4c4c4c'))
                : '#333333';
            drawRoundedRect(ctx, x, this.tileY, TILE_W, TILE_H, TILE_RADIUS);

            text({
                context: ctx,
                text: player ? player.nickname.toUpperCase() : '?',
                x: x + TILE_W / 2,
                y: this.tileY + SWITCH_LABEL_OFFSET_Y,
                fontSize: player ? '15px' : '16px',
                fillStyle: isMe ? '#FFFF00' : '#FFFFFF',
            });
        }
    }

    /** Rysuje przycisk dodawania bota do pokoju. */
    private renderAddBotButton(ctx: CanvasRenderingContext2D) {
        const playerCount = Object.keys(this.playersList).length;
        const canAdd = playerCount < 5 && !this.addingBot;

        ctx.fillStyle = canAdd ? '#2563eb' : '#444444';
        drawRoundedRect(ctx, this.botBtnX, this.botBtnY, BOT_BTN_W, BOT_BTN_H, BOT_BTN_RADIUS);

        text({
            context: ctx,
            text: this.addingBot ? 'DODAWANIE...' : '+ DODAJ MISTRZA (BOT)',
            x: this.botBtnX + BOT_BTN_W / 2,
            y: this.botBtnY + BOT_BTN_H / 2 + 5,
            fontSize: '13px',
            textAlign: 'center',
            fillStyle: '#FFFFFF',
        });
    }

    /** Rysuje przełącznik gotowości gracza. */
    private renderReadySwitch(ctx: CanvasRenderingContext2D) {
        ctx.fillStyle = this.amIReady ? '#bbf7d0' : '#cccccc';
        drawRoundedRect(ctx, this.switchX, this.switchY, SWITCH_W, SWITCH_H, SWITCH_RADIUS);

        const knobX = this.amIReady
            ? this.switchX + SWITCH_W - SWITCH_KNOB_INSET
            : this.switchX + SWITCH_KNOB_INSET;

        ctx.fillStyle = '#FFFFFF';
        ctx.beginPath();
        ctx.arc(knobX, this.switchY + SWITCH_H / 2, SWITCH_KNOB_RADIUS, 0, Math.PI * 2);
        ctx.fill();

        text({
            context: ctx,
            text: this.amIReady ? 'CHCĘ JUŻ GRAĆ!' : 'CZEKAM NA INNYCH GRACZY',
            x: this.switchX + SWITCH_W / 2,
            y: this.switchY + SWITCH_H + 22,
            fontSize: '15px',
            textAlign: 'center',
        });
    }

    /** Obsługuje kliknięcia w gotowość lub dodanie bota. */
    public onMouseDown(mx: number, my: number) {
        const inSwitch = mx > this.switchX && mx < this.switchX + SWITCH_W
            && my > this.switchY && my < this.switchY + SWITCH_H;
        if (inSwitch) {
            this.toggleReady();
            return;
        }

        const inBotBtn = mx > this.botBtnX && mx < this.botBtnX + BOT_BTN_W
            && my > this.botBtnY && my < this.botBtnY + BOT_BTN_H;
        if (inBotBtn) this.addBot();
    }

    /** Przełącza gotowość gracza i synchronizuje ją z serwerem. */
    private async toggleReady() {
        this.amIReady = !this.amIReady;
        try {
            const params = new URLSearchParams({
                action: 'ready',
                room_id: String(this.context.roomId),
                player_id: String(this.context.playerId),
                token: this.context.token,
                ready: String(this.amIReady),
            });
            const res = await fetch(`${API_ENDPOINTS.GAME}?${params}`);
            const data = await res.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Nie udało się zmienić gotowości');
            }
            this.fetchLobbyState();
        } catch (e) {
            console.error('Ready toggle error:', e);
            this.amIReady = !this.amIReady;
        }
    }

    /** Dodaje bota do lobby, jeśli są wolne miejsca. */
    private async addBot() {
        if (this.addingBot || Object.keys(this.playersList).length >= 5) return;
        this.addingBot = true;
        try {
            const params = new URLSearchParams({
                action: 'addBot',
                room_id: String(this.context.roomId),
                player_id: String(this.context.playerId),
                token: this.context.token,
            });
            const res = await fetch(`${API_ENDPOINTS.GAME}?${params}`);
            const data = await res.json();
            if (data.status !== 'success') {
                console.error('Add bot error:', data.message);
            }
            await this.fetchLobbyState();
        } catch (e) {
            console.error('Add bot error:', e);
        } finally {
            this.addingBot = false;
        }
    }

    /** Zachowuje kontrakt sceny bez skrótów klawiaturowych w lobby. */
    public onKeyDown(_e: KeyboardEvent) {
    }

    /** Zachowuje kontrakt sceny bez obsługi ruchu myszy. */
    public onMouseMove(_mx: number, _my: number) {
    }

    /** Zatrzymuje cykliczne odświeżanie lobby. */
    public onDestroy() {
        clearInterval(this.pollingInterval);
    }
}
