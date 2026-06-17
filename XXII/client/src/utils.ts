import {TURTLE_COLOR_MAP} from './constants.js';

/** Normalizuje nazwę koloru do klucza używanego w sprite'ach. */
export function normalizeColor(color: string): string {
    return TURTLE_COLOR_MAP[color] ?? color;
}

/** Buduje nazwę sprite'a karty na podstawie koloru i ruchu. */
export function buildCardKey(color: string, move: string): string {
    const moveStr = String(move ?? '');

    if (moveStr.includes('LF')) {
        return moveStr.includes('2') ? 'last_plus_2' : 'last_plus_1';
    }

    const colorKey = color === 'any' ? 'multi' : normalizeColor(color);
    const moveKey = moveStr.replace('+', 'plus_').replace('-', 'minus_');
    return `${colorKey}_${moveKey}`;
}

/** Rysuje prostokąt z zaokrąglonymi rogami na canvasie. */
export function drawRoundedRect(
    ctx: CanvasRenderingContext2D,
    x: number, y: number,
    w: number, h: number,
    r: number
): void {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,0.5)';
    ctx.lineWidth = 1.5;
    ctx.stroke();
}
