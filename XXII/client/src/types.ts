export interface Player {
    nickname: string;
    is_ready: boolean;
    is_bot?: boolean;
}

export interface RoomState {
    status: string;
    players: { [key: string]: Player };
}

export interface GameContext {
    canvas: HTMLCanvasElement;
    ctx: CanvasRenderingContext2D;
    roomId: number;
    playerId: number;
    nickname: string;
    token: string;
    changeScene: (newScene: GameScene) => void;
    assets: {
        spritesheet: HTMLImageElement | null;
        board: HTMLImageElement | null;
        spritesData: any | null;
    };
}

export abstract class GameScene {
    protected context!: GameContext;

    /** Przypina współdzielony kontekst i uruchamia inicjalizację sceny. */
    public setContext(context: GameContext) {
        this.context = context;
        this.onInit();
    }

    /** Przygotowuje stan sceny po jej aktywowaniu. */
    public abstract onInit(): void;

    /** Reaguje na zmianę rozmiaru canvasa. */
    public abstract onResize(width: number, height: number): void;

    /** Aktualizuje stan sceny między klatkami. */
    public abstract onUpdate(): void;

    /** Rysuje aktualną klatkę sceny. */
    public abstract onRender(): void;

    /** Obsługuje kliknięcie myszy we współrzędnych canvasa. */
    public abstract onMouseDown(mx: number, my: number): void;

    /** Obsługuje ruch myszy we współrzędnych canvasa. */
    public abstract onMouseMove(mx: number, my: number): void;

    /** Obsługuje naciśnięcie klawisza podczas aktywnej sceny. */
    public abstract onKeyDown(e: KeyboardEvent): void;

    /** Sprząta zasoby sceny przed przełączeniem na kolejną. */
    public onDestroy(): void {
    }
}
