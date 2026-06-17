export interface CardData {
    id: string;
    color: string;
    move: string;
}

export interface ChatMessage {
    id: number;
    user: string;
    text: string;
}

export interface Player {
    id: number;
    nickname: string;
    is_bot?: boolean;
    cards?: CardData[];
    color?: string;
}

export interface WinnerData {
    color: string;
    winner_name: string;
}

export interface TurtlePosition {
    x: number;
    y: number;
}

export interface ColorModalRegion {
    color: string;
    path: Path2D;
}

export interface BoardDimensions {
    drawW: number;
    drawH: number;
    offsetY: number;
}