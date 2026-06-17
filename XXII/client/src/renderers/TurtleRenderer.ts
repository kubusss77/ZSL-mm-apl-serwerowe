import { BOARD, COLOR_NAMES_PL, TOOLTIP } from '../constants.js';
import { TurtlePosition } from '../GameplayTypes.js';
import { normalizeColor } from '../utils.js';
import { text } from '../SceneHelper.js';

type Sprites = Record<string, { x: number; y: number; w: number; h: number }>;

/** Rysuje żółwie na planszy z animowanym dojściem do pól. */
export function renderTurtles(
  ctx: CanvasRenderingContext2D,
  img: HTMLImageElement,
  sprites: Sprites,
  boardState: string[][],
  turtlePositions: Record<string, TurtlePosition>,
  fieldPositions: [number, number][],
  drawW: number,
  mouseX: number,
  mouseY: number,
  colorModalOpen: boolean,
): number {
  if (!boardState?.length) return -1;

  const scale = drawW / BOARD.BASE_SCALE_REF_WIDTH;
  const tokenH = BOARD.BASE_TURTLE_HEIGHT * scale;
  const stackOffset = BOARD.STACK_OFFSET * scale;
  const startGap = BOARD.START_ROW_GAP * scale;
  const fallbackTokenW = tokenH * (2 / 3);

  let hoveredFieldIdx = -1;

  boardState.forEach((stack, fieldIdx) => {
    const target = fieldPositions[fieldIdx];
    if (!target) return;

    const isStartField = fieldIdx === 0;
    const stackHeight = !isStartField && stack.length > 0 ? (stack.length - 1) * stackOffset : 0;
    const startRowOffset = isStartField ? ((stack.length - 1) * startGap) / 2 : 0;
    const hitX = isStartField ? target[0] - startRowOffset : target[0];
    const hitW = isStartField
      ? Math.max(fallbackTokenW, (stack.length - 1) * startGap + fallbackTokenW)
      : fallbackTokenW;
    const hitBox = new Path2D();
    hitBox.rect(hitX, target[1] - stackHeight, hitW, tokenH + stackHeight);

    if (!colorModalOpen && ctx.isPointInPath(hitBox, mouseX, mouseY)) {
      hoveredFieldIdx = fieldIdx;
    }

    stack.forEach((turtleColor, heightIdx) => {
      const pos = turtlePositions[turtleColor] ?? { x: target[0], y: target[1] };
      const spriteName = `turtle_hat_${normalizeColor(turtleColor)}`;
      const s = sprites[spriteName];
      if (!s) return;
      const tokenW = tokenH * (s.w / s.h);

      ctx.save();
      ctx.shadowColor = hoveredFieldIdx === fieldIdx ? '#FFFF00' : 'rgba(0,0,0,0.5)';
      ctx.shadowBlur = (hoveredFieldIdx === fieldIdx ? 10 : 4) * scale;
      const drawX = isStartField ? pos.x : pos.x;
      const drawY = isStartField ? pos.y : pos.y - heightIdx * stackOffset;
      ctx.drawImage(img, s.x, s.y, s.w, s.h, drawX, drawY, tokenW, tokenH);
      ctx.restore();
    });
  });

  if (hoveredFieldIdx !== -1) {
    renderTooltip(ctx, hoveredFieldIdx, boardState[hoveredFieldIdx], mouseX, mouseY);
  }

  return hoveredFieldIdx;
}

/** Rysuje podpowiedź z kolejnością żółwi na wskazanym polu. */
function renderTooltip(
  ctx: CanvasRenderingContext2D,
  fieldIdx: number,
  stack: string[],
  mouseX: number,
  mouseY: number,
): void {
  if (!stack?.length) return;

  const tooltipW = TOOLTIP.WIDTH;
  const tooltipH = TOOLTIP.HEADER_HEIGHT + stack.length * TOOLTIP.ROW_HEIGHT;
  const canvasW = ctx.canvas.width;
  const canvasH = ctx.canvas.height;

  let x = mouseX + 20;
  let y = mouseY - tooltipH / 2;
  if (x + tooltipW > canvasW) x = mouseX - tooltipW - 20;
  if (y < TOOLTIP.MARGIN) y = TOOLTIP.MARGIN;
  if (y + tooltipH > canvasH) y = canvasH - tooltipH - TOOLTIP.MARGIN;

  ctx.save();
  ctx.fillStyle = 'rgba(0,0,0,0.9)';
  ctx.strokeStyle = '#FFFF00';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.roundRect(x, y, tooltipW, tooltipH, 8);
  ctx.fill();
  ctx.stroke();

  text({ context: ctx, fontSize: '11px', x: x + tooltipW / 2, y: y + 18, text: `POLE ${fieldIdx + 1}` });

  [...stack].reverse().forEach((rawColor, i) => {
    const key = normalizeColor(rawColor);
    const label = COLOR_NAMES_PL[key] ?? key.toUpperCase();
    const isTop = i === 0;
    const colorMap: Record<string, string> = {
      red: '#FF4d4d',
      green: '#55FF55',
      blue: '#55FFFF',
      yellow: '#FFFF55',
      violet: '#FF55FF',
    };

    text({
      context: ctx,
      fontWeight: isTop ? 'bold' : 'normal',
      fontSize: '13px',
      x: x + 60,
      y: y + 42 + i * TOOLTIP.ROW_SPACING + 1,
      text: label,
      fillStyle: colorMap[key] ?? '#FFFFFF',
    });
  });

  ctx.restore();
}
