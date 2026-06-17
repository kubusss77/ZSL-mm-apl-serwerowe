import {CARD, SECRET_CARD} from '../constants.js';
import {CardData} from '../GameplayTypes.js';
import {buildCardKey} from '../utils.js';

type Sprites = Record<string, { x: number; y: number; w: number; h: number }>;

/** Rysuje wachlarz kart gracza i zwraca ich klikalne obszary. */
export function renderCardsFan(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    sprites: Sprites,
    cards: CardData[],
    canvasW: number,
    canvasH: number,
    mouseX: number,
    mouseY: number,
    colorModalOpen: boolean
): number {
    const fanCenterX = canvasW * CARD.FAN_CENTER_X_RATIO;
    const fanCenterY = canvasH + CARD.FAN_CENTER_Y_OFFSET;

    let hoveredIdx = -1;
    if (!colorModalOpen) {
        for (let i = 0; i < cards.length; i++) {
            ctx.save();
            ctx.translate(fanCenterX, fanCenterY);
            ctx.rotate((CARD.FAN_ANGLES[i] * Math.PI) / 180);

            const baseY = -CARD.HEIGHT - CARD.FAN_VERTICAL_OFFSET;
            const hitArea = new Path2D();
            hitArea.rect(
                -CARD.WIDTH / 2,
                baseY,
                CARD.WIDTH,
                CARD.HEIGHT
            );

            if (ctx.isPointInPath(hitArea, mouseX, mouseY)) {
                hoveredIdx = i;
            }
            ctx.restore();
        }
    }

    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const cardKey = buildCardKey(card.color, card.move);
        const s = sprites[cardKey] ?? sprites['card_back'];
        const isHovered = hoveredIdx === i;

        ctx.save();
        ctx.translate(fanCenterX, fanCenterY);
        ctx.rotate((CARD.FAN_ANGLES[i] * Math.PI) / 180);

        const drawX = -CARD.WIDTH / 2;
        const drawY = -CARD.HEIGHT - CARD.FAN_VERTICAL_OFFSET;

        if (isHovered) {
            ctx.save();
            ctx.shadowColor = 'yellow';
            ctx.shadowBlur = CARD.HOVER_SHADOW_BLUR;
            ctx.strokeStyle = 'yellow';
            ctx.lineWidth = CARD.HOVER_STROKE_WIDTH;
            ctx.strokeRect(drawX, drawY, CARD.WIDTH, CARD.HEIGHT);
            ctx.restore();
        }

        if (s) {
            ctx.drawImage(img, s.x, s.y, s.w, s.h, drawX, drawY, CARD.WIDTH, CARD.HEIGHT);
        } else {
            ctx.fillStyle = 'purple';
            ctx.fillRect(drawX, drawY, CARD.WIDTH, CARD.HEIGHT);
        }

        ctx.restore();
    }

    return hoveredIdx;
}

/** Rysuje kartę tajnego koloru gracza. */
export function renderSecretCard(
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    sprites: Sprites,
    canvasW: number,
    secretColor: string,
    mouseX: number,
    mouseY: number
): void {
    const destX = canvasW - SECRET_CARD.OFFSET_X;
    const {Y: destY} = SECRET_CARD;

    const path = new Path2D();
    path.rect(destX, destY, CARD.WIDTH, CARD.HEIGHT);
    const isHovered = ctx.isPointInPath(path, mouseX, mouseY);

    const spriteName = isHovered ? secretColor : 'card_back';
    const s = sprites[spriteName];
    if (!s) return;

    const previewW = isHovered ? SECRET_CARD.TURTLE_WIDTH : SECRET_CARD.BACK_WIDTH;
    const previewH = previewW * (s.h / s.w);
    const previewX = destX + CARD.WIDTH / 2 - previewW / 2;
    const previewY = destY + CARD.HEIGHT / 2 - previewH / 2;

    if (isHovered) {
        ctx.shadowColor = 'yellow';
        ctx.shadowBlur = SECRET_CARD.SHADOW_BLUR;
    }

    ctx.drawImage(img, s.x, s.y, s.w, s.h, previewX, previewY, previewW, previewH);
    ctx.shadowBlur = 0;
}
