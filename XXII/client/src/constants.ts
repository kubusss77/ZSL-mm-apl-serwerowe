export const BOARD = {
    ASPECT_RATIO: 2.5,
    INNER_ASPECT_RATIO: 1.8,
    BASE_TURTLE_HEIGHT: 86,
    BASE_SCALE_REF_WIDTH: 1800,
    STACK_OFFSET: 28,
    START_ROW_GAP: 54,
    LERP_FACTOR: 0.15,
} as const;

export const FIELD_MAP: { x: number; y: number }[] = [
    {x: 0.51, y: 0.76},
    {x: 0.455, y: 0.70},
    {x: 0.42, y: 0.64},
    {x: 0.44, y: 0.57},
    {x: 0.495, y: 0.50},
    {x: 0.52, y: 0.43},
    {x: 0.48, y: 0.37},
    {x: 0.43, y: 0.31},
    {x: 0.445, y: 0.235},
    {x: 0.49, y: 0.18},
];

export const CARD = {
    WIDTH: 110,
    HEIGHT: 160,
    FAN_CENTER_X_RATIO: 0.82,
    FAN_CENTER_Y_OFFSET: 120,
    FAN_ANGLES: [-30, -15, 0, 15, 30],
    FAN_VERTICAL_OFFSET: 200,
    HOVER_SHADOW_BLUR: 20,
    HOVER_STROKE_WIDTH: 6,
} as const;

export const CHAT = {
    WIDTH: 380,
    HEIGHT: 250,
    MARGIN: 20,
    MAX_MESSAGES: 12,
    MAX_INPUT_LENGTH: 40,
    CURSOR_BLINK_MS: 500,
} as const;

export const COLOR_MODAL = {
    WIDTH: 500,
    HEIGHT: 200,
    TOKEN_WIDTH: 70,
    TOKEN_HEIGHT: 50,
    TOKEN_SPACING: 85,
    TOKEN_START_OFFSET_X: 45,
    TOKEN_Y_OFFSET: 65,
    CANCEL_WIDTH: 120,
    CANCEL_HEIGHT: 32,
    CANCEL_BOTTOM_OFFSET: 45,
    SHADOW_BLUR: 30,
} as const;

export const PLAYER_BAR = {
    Y: 20,
    ITEM_WIDTH: 160,
    ITEM_HEIGHT: 50,
    GAP: 15,
    TIMER_RADIUS: 18,
    TIMER_OFFSET_X: 65,
    LOW_TIME_THRESHOLD: 5,
} as const;

export const HUD = {
    DECK_X: 20,
    DECK_Y: 160,
    TURN_BAR_Y: 52,
    TURN_BAR_SCALE: 7,
    LOW_TIME_THRESHOLD: 10,
    WIN_BG_W: 600,
    WIN_BG_H: 200,
    EXIT_BTN_W: 160,
    EXIT_BTN_H: 36,
    EXIT_BTN_OFFSET_Y: 30,
} as const;

export const SECRET_CARD = {
    OFFSET_X: 200,
    Y: 50,
    BACK_WIDTH: 120,
    TURTLE_WIDTH: 120,
    SHADOW_BLUR: 20,
} as const;

export const TOOLTIP = {
    WIDTH: 120,
    HEADER_HEIGHT: 30,
    ROW_HEIGHT: 25,
    ROW_SPACING: 23,
    MARGIN: 10,
} as const;

export const VIGNETTE_OPACITY = 0.4;
export const POLL_INTERVAL_MS = 800;


export const TURTLE_COLORS = ['red', 'green', 'blue', 'yellow', 'purple'] as const;

export const COLOR_NAMES_PL: Record<string, string> = {
    red: 'Czerwony',
    green: 'Zielony',
    blue: 'Niebieski',
    yellow: 'Żółty',
    purple: 'Fioletowy',
    violet: 'Fioletowy',
};

export const TURTLE_COLOR_MAP: Record<string, string> = {
    purple: 'violet',
};

export const ASSETS = {
    SPRITESHEET_SRC: 'assets/img.png',
    BOARD_SRC: 'assets/board-full.png',
    SPRITES_JSON_SRC: 'assets/sprites.json',
} as const;

export const API_ENDPOINTS = {
    GAME: 'server/Http/api.php',
    CHAT_POLL: 'server/Http/chat_poll.php',
    CHAT_SEND: 'server/Http/chat_send.php',
} as const;

export const SESSION_KEY = 'pedzace_zolwie_session';
