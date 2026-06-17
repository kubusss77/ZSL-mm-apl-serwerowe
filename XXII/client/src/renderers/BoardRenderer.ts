import {BOARD, VIGNETTE_OPACITY} from '../constants.js';
import {BoardDimensions} from '../GameplayTypes.js';

/** Wylicza rozmiar i pozycję planszy dla aktualnego canvasa. */
export function getBoardDimensions(canvasW: number, canvasH: number): BoardDimensions {
    const drawH = canvasH;
    const drawW = canvasH * BOARD.INNER_ASPECT_RATIO;
    return {drawW, drawH, offsetY: 0};
}

/** Rysuje planszę albo tło awaryjne, jeśli obraz nie jest gotowy. */
export function renderBoard(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement | null,
    canvasW: number,
    canvasH: number
): void {
    if (!img) return;
    const drawH = canvasH;
    const drawW = canvasH * BOARD.ASPECT_RATIO;
    const offsetX = (canvasW - drawW) / 2;
    ctx.drawImage(img, offsetX, 0, drawW, drawH);
}

/** Nakłada przyciemnienie brzegów ekranu. */
export function renderVignette(
    ctx: CanvasRenderingContext2D,
    canvasW: number,
    canvasH: number
): void {
    const gradient = ctx.createRadialGradient(
        canvasW / 2, canvasH / 2, 0,
        canvasW / 2, canvasH / 2, canvasW / 1.2
    );
    gradient.addColorStop(0, 'rgba(0,0,0,0)');
    gradient.addColorStop(1, `rgba(0,0,0,${VIGNETTE_OPACITY})`);
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, canvasW, canvasH);
}

/** Przelicza względne współrzędne pól na piksele. */
export function calibrateFieldPositions(
    canvasW: number,
    canvasH: number,
    fieldMap: { x: number; y: number }[]
): [number, number][] {
    const {drawW, drawH, offsetY} = getBoardDimensions(canvasW, canvasH);
    const centerX = canvasW / 2;

    return fieldMap.map(({x, y}) => [
        centerX + ((x - 0.5) * drawW),
        offsetY + drawH * y,
    ]);
}
