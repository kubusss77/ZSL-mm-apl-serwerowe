import {CHAT} from '../constants.js';
import {ChatMessage} from '../GameplayTypes.js';
import {text} from '../SceneHelper.js';

/** Rysuje panel czatu z historią i aktualnym tekstem wejściowym. */
export function renderChat(
    ctx: CanvasRenderingContext2D,
    canvasH: number,
    messages: ChatMessage[],
    currentInput: string,
    isActive: boolean
): void {
    const x = CHAT.MARGIN;
    const y = canvasH - CHAT.HEIGHT - CHAT.MARGIN;

    ctx.fillStyle = isActive ? 'rgba(0,0,0,0.85)' : 'rgba(0,0,0,0.6)';
    ctx.fillRect(x, y, CHAT.WIDTH, CHAT.HEIGHT);
    ctx.strokeStyle = isActive ? '#FFFF00' : '#888888';
    ctx.lineWidth = 2;
    ctx.strokeRect(x, y, CHAT.WIDTH, CHAT.HEIGHT);

    messages.forEach((m, i) => {
        text({
            context: ctx,
            textAlign: 'left',
            fontSize: '14px',
            fontWeight: 'normal',
            x: x + 10,
            y: y + 15 + i * 18,
            text: `${m.user}: ${m.text}`,
        });
    });

    const inputY = y + CHAT.HEIGHT - 30;
    ctx.fillStyle = 'rgba(255,255,255,0.1)';
    ctx.fillRect(x + 5, inputY, CHAT.WIDTH - 10, 25);

    const cursor = isActive && Math.floor(Date.now() / CHAT.CURSOR_BLINK_MS) % 2 === 0 ? '|' : '';
    const displayText = isActive ? currentInput + cursor : 'Kliknij tutaj lub wciśnij Enter, aby pisać...';

    text({
        context: ctx,
        fillStyle: isActive ? '#FFFF00' : '#aaaaaa',
        text: displayText,
        x: x + 12,
        y: inputY + 17,
        fontSize: '12px',
        textAlign: 'left',
    });
}

/** Sprawdza, czy punkt myszy znajduje się w obszarze czatu. */
export function isChatArea(
    mx: number,
    my: number,
    canvasH: number
): boolean {
    const x = CHAT.MARGIN;
    const y = canvasH - CHAT.HEIGHT - CHAT.MARGIN;
    return mx >= x && mx <= x + CHAT.WIDTH && my >= y && my <= y + CHAT.HEIGHT;
}
