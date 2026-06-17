import {COLOR_MODAL, TURTLE_COLORS} from '../constants.js';
import {ColorModalRegion} from '../GameplayTypes.js';
import {normalizeColor} from '../utils.js';
import {text} from '../SceneHelper.js';

type Sprites = Record<string, { x: number; y: number; w: number; h: number }>;

export interface ColorModalResult {
    regions: ColorModalRegion[];
    cancelPath: Path2D;
}

/** Rysuje modal wyboru koloru dla kart uniwersalnych. */
export function renderColorSelectionModal(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    sprites: Sprites,
    canvasW: number,
    canvasH: number,
    mouseX: number,
    mouseY: number
): ColorModalResult {
    ctx.save();

    ctx.fillStyle = 'rgba(0,0,0,0.6)';
    ctx.fillRect(0, 0, canvasW, canvasH);

    const modalX = (canvasW - COLOR_MODAL.WIDTH) / 2;
    const modalY = (canvasH - COLOR_MODAL.HEIGHT) / 2;

    ctx.fillStyle = 'rgba(20,35,15,0.95)';
    ctx.strokeStyle = '#FFFF00';
    ctx.lineWidth = 3;
    ctx.shadowColor = 'black';
    ctx.shadowBlur = COLOR_MODAL.SHADOW_BLUR;
    ctx.beginPath();
    ctx.roundRect(modalX, modalY, COLOR_MODAL.WIDTH, COLOR_MODAL.HEIGHT, 15);
    ctx.fill();
    ctx.stroke();
    ctx.shadowBlur = 0;

    text({
        context: ctx,
        text: 'WYBIERZ ŻÓŁWIA DO RUCHU',
        x: modalX + COLOR_MODAL.WIDTH / 2,
        y: modalY + 35,
        fontSize: '18px',
    });

    const regions: ColorModalRegion[] = [];
    const tokenY = modalY + COLOR_MODAL.TOKEN_Y_OFFSET;
    const startX = modalX + COLOR_MODAL.TOKEN_START_OFFSET_X;

    TURTLE_COLORS.forEach((color, idx) => {
        const x = startX + idx * COLOR_MODAL.TOKEN_SPACING;
        const path = new Path2D();
        path.rect(x, tokenY, COLOR_MODAL.TOKEN_WIDTH, COLOR_MODAL.TOKEN_HEIGHT);
        regions.push({color, path});

        const s = sprites[`turtle_token_${normalizeColor(color)}`];
        if (!s) return;

        const isHovered = ctx.isPointInPath(path, mouseX, mouseY);

        ctx.save();
        if (isHovered) {
            ctx.shadowColor = '#FFFF00';
            ctx.shadowBlur = 15;
            ctx.drawImage(img, s.x, s.y, s.w, s.h, x, tokenY - 5, COLOR_MODAL.TOKEN_WIDTH, COLOR_MODAL.TOKEN_HEIGHT);
        } else {
            ctx.drawImage(img, s.x, s.y, s.w, s.h, x, tokenY, COLOR_MODAL.TOKEN_WIDTH, COLOR_MODAL.TOKEN_HEIGHT);
        }
        ctx.restore();
    });

    const cancelX = modalX + (COLOR_MODAL.WIDTH - COLOR_MODAL.CANCEL_WIDTH) / 2;
    const cancelY = modalY + COLOR_MODAL.HEIGHT - COLOR_MODAL.CANCEL_BOTTOM_OFFSET;
    const cancelPath = new Path2D();
    cancelPath.rect(cancelX, cancelY, COLOR_MODAL.CANCEL_WIDTH, COLOR_MODAL.CANCEL_HEIGHT);

    const isCancelHovered = ctx.isPointInPath(cancelPath, mouseX, mouseY);
    ctx.fillStyle = isCancelHovered ? '#d32f2f' : '#b71c1c';
    ctx.beginPath();
    ctx.roundRect(cancelX, cancelY, COLOR_MODAL.CANCEL_WIDTH, COLOR_MODAL.CANCEL_HEIGHT, 6);
    ctx.fill();
    ctx.strokeStyle = '#FFFFFF';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    text({context: ctx, fontSize: '13px', text: 'ANULUJ', x: cancelX + COLOR_MODAL.CANCEL_WIDTH / 2, y: cancelY + 20});

    ctx.restore();

    return {regions, cancelPath};
}
