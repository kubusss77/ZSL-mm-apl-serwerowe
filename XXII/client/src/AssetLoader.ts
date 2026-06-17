import {ASSETS} from './constants.js';

export interface LoadedAssets {
    spritesheet: HTMLImageElement;
    board: HTMLImageElement;
    spritesData: Record<string, { x: number; y: number; w: number; h: number }>;
}

/** Ładuje pojedynczy obraz i zwraca go po zdarzeniu onload. */
function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
        img.src = src;
    });
}

/** Ładuje komplet grafik i danych sprite'ów potrzebnych klientowi. */
export async function loadAssets(): Promise<LoadedAssets> {
    const [spritesheet, board] = await Promise.all([
        loadImage(ASSETS.SPRITESHEET_SRC),
        loadImage(ASSETS.BOARD_SRC),
    ]);

    const res = await fetch(ASSETS.SPRITES_JSON_SRC);
    if (!res.ok) throw new Error(`Failed to load sprites JSON: ${res.statusText}`);
    const spritesData = await res.json();

    return {spritesheet, board, spritesData};
}
