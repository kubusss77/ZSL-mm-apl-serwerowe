import {CARD, HUD, PLAYER_BAR} from '../constants.js';
import {CardData, Player, WinnerData} from '../GameplayTypes.js';
import {buildCardKey} from '../utils.js';
import {text} from '../SceneHelper.js';

type Sprites = Record<string, { x: number; y: number; w: number; h: number }>;

export interface HudExitBtn {
    x: number;
    y: number;
}

/** Rysuje pasek graczy z oznaczeniem aktywnej tury. */
export function renderPlayerBar(
    ctx: CanvasRenderingContext2D,
    players: Player[],
    currentTurn: number,
    myPlayerId: number,
    timeLeft: number,
    winner: WinnerData | null
): void {
    const totalW = players.length * (PLAYER_BAR.ITEM_WIDTH + PLAYER_BAR.GAP);
    let x = (ctx.canvas.width - totalW) / 2;

    players.forEach((player) => {
        const isCurrent = player.id === currentTurn;
        const isMe = player.id === myPlayerId;

        ctx.fillStyle = isCurrent ? '#1a3a5a' : '#444';
        ctx.strokeStyle = isCurrent ? '#FFFF55' : '#fff';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.roundRect(x, PLAYER_BAR.Y, PLAYER_BAR.ITEM_WIDTH, PLAYER_BAR.ITEM_HEIGHT, 25);
        ctx.fill();
        ctx.stroke();

        text({
            context: ctx,
            text: player.nickname,
            x: x + PLAYER_BAR.ITEM_WIDTH / 2,
            y: PLAYER_BAR.Y + PLAYER_BAR.ITEM_HEIGHT / 2 + 6,
            fillStyle: isMe ? '#FFFF55' : '#fff',
            fontSize: '16px',
        });

        if (isCurrent && !winner) {
            renderPlayerTimer(ctx, x + PLAYER_BAR.ITEM_WIDTH / 2 - 10, PLAYER_BAR.Y + 5, timeLeft);
        }

        x += PLAYER_BAR.ITEM_WIDTH + PLAYER_BAR.GAP;
    });
}

/** Rysuje licznik czasu dla aktywnego gracza. */
function renderPlayerTimer(
    ctx: CanvasRenderingContext2D,
    centerX: number,
    topY: number,
    timeLeft: number
): void {
    ctx.save();
    ctx.fillStyle = timeLeft < PLAYER_BAR.LOW_TIME_THRESHOLD ? '#FF4444' : '#333';
    ctx.beginPath();
    ctx.arc(centerX + PLAYER_BAR.TIMER_OFFSET_X, topY, PLAYER_BAR.TIMER_RADIUS, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.stroke();

    text({
        context: ctx,
        text: timeLeft.toString(),
        x: centerX + PLAYER_BAR.TIMER_OFFSET_X,
        y: topY + 5,
        fillStyle: '#fff',
        fontSize: '14px',
    });
    ctx.restore();
}

/** Rysuje elementy HUD gry i zwraca pozycję przycisku wyjścia. */
export function renderHUD(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    sprites: Sprites,
    isMyTurn: boolean,
    timeLeft: number,
    winner: WinnerData | null,
    cardsPlayedCount: number,
    lastPlayedCard: CardData | null,
    mouseX: number,
    mouseY: number
): HudExitBtn {
    const canvasW = ctx.canvas.width;
    const canvasH = ctx.canvas.height;
    let exitBtn: HudExitBtn = {x: HUD.DECK_X, y: HUD.DECK_Y};

    if (winner) {
        exitBtn = renderWinScreen(ctx, winner, canvasW, canvasH, mouseX, mouseY);
    } else {
        renderTurnStatus(ctx, isMyTurn, timeLeft);
    }

    renderDeckArea(ctx, img, sprites, cardsPlayedCount, lastPlayedCard);

    return exitBtn;
}

/** Rysuje ekran końca gry z przyciskiem opuszczenia. */
function renderWinScreen(
    ctx: CanvasRenderingContext2D,
    winner: WinnerData,
    canvasW: number,
    canvasH: number,
    mouseX: number,
    mouseY: number
): HudExitBtn {
    const cx = canvasW / 2;
    const cy = canvasH / 2;

    ctx.save();
    ctx.fillStyle = 'rgba(0,0,0,0.7)';
    ctx.beginPath();
    ctx.roundRect(cx - HUD.WIN_BG_W / 2, cy - HUD.WIN_BG_H / 2, HUD.WIN_BG_W, HUD.WIN_BG_H, 20);
    ctx.fill();
    ctx.restore();

    text({
        context: ctx,
        text: `KONIEC GRY! Wygrał żółw: ${winner.color.toUpperCase()}`,
        x: cx,
        y: cy - 40,
        fontSize: "32px",
        fillStyle: "#FFD700"
    })

    text({
        context: ctx,
        text: `Zwycięzca: ${winner.winner_name}`,
        x: cx,
        y: cy - 5,
        fontSize: "24px"
    })

    const btnX = cx - HUD.EXIT_BTN_W / 2;
    const btnY = cy + HUD.EXIT_BTN_OFFSET_Y;
    const isHovered = mouseX >= btnX && mouseX <= btnX + HUD.EXIT_BTN_W
        && mouseY >= btnY && mouseY <= btnY + HUD.EXIT_BTN_H;

    ctx.fillStyle = isHovered ? '#d32f2f' : '#b71c1c';
    ctx.fillRect(btnX, btnY, HUD.EXIT_BTN_W, HUD.EXIT_BTN_H);
    ctx.strokeStyle = '#FFFFFF';
    ctx.lineWidth = 2;
    ctx.strokeRect(btnX, btnY, HUD.EXIT_BTN_W, HUD.EXIT_BTN_H);

    text({
        context: ctx,
        fontSize: "16px",
        x: cx,
        y: btnY + 24,
        text: 'OPUŚĆ GRĘ'
    })

    return {x: btnX, y: btnY};
}

/** Rysuje komunikat aktualnej tury i pasek czasu. */
function renderTurnStatus(
    ctx: CanvasRenderingContext2D,
    isMyTurn: boolean,
    timeLeft: number
): void {
    if (isMyTurn) {
        text({
            context: ctx,
            text: `TWOJA TURA! Wybierz kartę. (Czas: ${timeLeft}s)`,
            x: 20, y: 40,
            fillStyle: '#00ff00',
            fontSize: '18px',
            textAlign: 'left',
        });
        ctx.fillStyle = timeLeft > HUD.LOW_TIME_THRESHOLD ? '#00FF00' : '#FF0000';
        ctx.fillRect(20, HUD.TURN_BAR_Y, Math.max(0, timeLeft * HUD.TURN_BAR_SCALE), 6);
    } else {
        text({
            context: ctx,
            text: `Oczekiwanie na ruch przeciwnika... (${timeLeft}s)`,
            fillStyle: '#ffa500',
            x: 20, y: 40,
            textAlign: 'left',
            fontSize: '18px',
        });
        ctx.fillStyle = timeLeft > HUD.LOW_TIME_THRESHOLD ? '#ffa500' : '#FF0000';
        ctx.fillRect(20, HUD.TURN_BAR_Y, Math.max(0, timeLeft * HUD.TURN_BAR_SCALE), 6);
    }
}

/** Rysuje obszar stosu kart z ostatnio zagraną kartą. */
function renderDeckArea(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    sprites: Sprites,
    cardsPlayedCount: number,
    lastPlayedCard: CardData | null
): void {
    const {DECK_X: x, DECK_Y: y} = HUD;

    text({
        context: ctx,
        text: `Zużyte karty: ${cardsPlayedCount} / 52`,
        x, y: y - 12,
        fontSize: '14px',
        textAlign: 'left',
    });

    if (lastPlayedCard) {
        const cardKey = buildCardKey(lastPlayedCard.color, lastPlayedCard.move);
        const s = sprites[cardKey] ?? sprites['card_back'];
        if (s) {
            ctx.drawImage(img, s.x, s.y, s.w, s.h, x, y, CARD.WIDTH, CARD.HEIGHT);
            ctx.strokeStyle = 'rgba(255,255,255,0.4)';
            ctx.lineWidth = 1;
            ctx.strokeRect(x, y, CARD.WIDTH, CARD.HEIGHT);
        }
    } else {
        ctx.strokeStyle = 'rgba(255,255,255,0.2)';
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 5]);
        ctx.strokeRect(x, y, CARD.WIDTH, CARD.HEIGHT);
        ctx.setLineDash([]);
        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        ctx.font = '12px Arial';
        ctx.fillText('Stos pusty', x + 24, y + 85);
    }
}
