import {GameScene} from './types.js';
import {LobbyScene} from './LobbyScene.js';
import {text} from './SceneHelper.js';
import {API_ENDPOINTS, SESSION_KEY} from './constants.js';

const INPUT_W = 450;
const INPUT_H = 40;
const BTN_RADIUS = 20;
const BTN_OFFSET_X = 40;
const MAX_NICK_LENGTH = 15;
const CURSOR_BLINK_MS = 500;
const CURSOR_W = 2;
const CURSOR_H = 24;
const CURSOR_OFFSET_Y = 8;
const TEXT_OFFSET_X = 12;
const TEXT_OFFSET_Y = 28;

export class NicknameScene extends GameScene {
    private inputX = 0;
    private inputY = 0;
    private btnX = 0;
    private btnY = 0;
    private showCursor = true;
    private cursorInterval!: number;
    private isLoading = false;

    /** Inicjalizuje formularz nicku i miganie kursora. */
    public onInit() {
        this.onResize(this.context.canvas.width, this.context.canvas.height);
        this.cursorInterval = window.setInterval(
            () => {
                this.showCursor = !this.showCursor;
            },
            CURSOR_BLINK_MS
        );
    }

    /** Ustawia położenie pola tekstowego i przycisku OK. */
    public onResize(width: number, height: number) {
        this.inputX = width / 2 - INPUT_W / 2;
        this.inputY = height / 2 - 20;
        this.btnX = this.inputX + INPUT_W + BTN_OFFSET_X;
        this.btnY = this.inputY + INPUT_H / 2;
    }

    /** Nie wykonuje cyklicznej logiki poza renderowaniem. */
    public onUpdate() {
    }

    /** Rysuje ekran wpisywania nicku. */
    public onRender() {
        const ctx = this.context.ctx;

        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, this.context.canvas.width, this.context.canvas.height);

        text({
            context: ctx,
            text: 'TWÓJ NICK:',
            x: this.inputX - 15,
            y: this.inputY + TEXT_OFFSET_Y,
            textAlign: 'right',
            fontSize: '24px',
        });

        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(this.inputX, this.inputY, INPUT_W, INPUT_H);

        text({
            context: ctx,
            text: this.context.nickname,
            x: this.inputX + TEXT_OFFSET_X,
            y: this.inputY + TEXT_OFFSET_Y,
            fontSize: '22px',
            fillStyle: this.isLoading ? '#888888' : '#000000',
            textAlign: 'left',
        });

        if (this.showCursor && !this.isLoading) {
            const textWidth = ctx.measureText(this.context.nickname).width;
            ctx.fillStyle = '#000000';
            ctx.fillRect(
                this.inputX + TEXT_OFFSET_X + 2 + textWidth,
                this.inputY + CURSOR_OFFSET_Y,
                CURSOR_W,
                CURSOR_H
            );
        }

        ctx.strokeStyle = this.isLoading ? '#888888' : '#FFFF00';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.arc(this.btnX, this.btnY, BTN_RADIUS, 0, Math.PI * 2);
        ctx.stroke();

        text({
            context: ctx,
            text: this.isLoading ? '...' : 'OK',
            x: this.btnX,
            y: this.btnY + 6,
            fontSize: '16px',
        });
    }

    /** Obsługuje wpisywanie, Enter i Backspace w polu nicku. */
    public onKeyDown(e: KeyboardEvent) {
        if (this.isLoading) return;

        if (e.key === 'Backspace') {
            this.context.nickname = this.context.nickname.slice(0, -1);
        } else if (e.key === 'Enter') {
            this.submit();
        } else if (e.key.length === 1 && this.context.nickname.length < MAX_NICK_LENGTH) {
            if (e.key === ' ' && this.context.nickname.length === 0) return;
            this.context.nickname += e.key;
        }
    }

    /** Wysyła formularz po kliknięciu przycisku OK. */
    public onMouseDown(mx: number, my: number) {
        if (this.isLoading) return;
        const dist = Math.hypot(mx - this.btnX, my - this.btnY);
        if (dist <= BTN_RADIUS) this.submit();
    }

    /** Zachowuje zgodność z kontraktem sceny bez obsługi hovera. */
    public onMouseMove(_mx: number, _my: number): void {
    }

    /** Dołącza do pokoju i zapisuje dane sesji w localStorage. */
    private async submit() {
        const nickname = this.context.nickname.trim();
        if (!nickname || this.isLoading) return;

        this.isLoading = true;

        try {
            const res = await fetch(`${API_ENDPOINTS.GAME}?action=join&nickname=${encodeURIComponent(nickname)}`);
            const data = await res.json();

            if (data.status !== 'success') {
                alert(data.message || 'Wystąpił błąd podczas dołączania do pokoju.');
                this.isLoading = false;
                return;
            }

            this.context.roomId = data.roomId;
            this.context.playerId = data.playerId;
            this.context.token = data.token;
            this.context.nickname = nickname;

            localStorage.setItem(SESSION_KEY, JSON.stringify({
                roomId: data.roomId,
                playerId: data.playerId,
                token: data.token,
                nickname,
            }));

            this.context.changeScene(new LobbyScene());
        } catch (e) {
            console.error('Join error:', e);
            alert('Nie można połączyć się z serwerem gry.');
            this.isLoading = false;
        }
    }

    /** Zatrzymuje interwał migania kursora. */
    public onDestroy() {
        clearInterval(this.cursorInterval);
    }
}
