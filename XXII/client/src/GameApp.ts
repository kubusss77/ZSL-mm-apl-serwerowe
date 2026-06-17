import {GameContext, GameScene} from './types.js';
import {NicknameScene} from './NicknameScene.js';
import {LobbyScene} from './LobbyScene.js';
import {GameplayScene} from './GameplayScene.js';
import {loadAssets} from './AssetLoader.js';
import {clearSession, loadSession, reconnect} from './api/SessionApi.js';

class GameApp {
    private readonly context: GameContext;
    private currentScene!: GameScene;
    private isInitialized = false;

    /** Tworzy kontekst aplikacji i uruchamia ładowanie zasobów oraz sesji. */
    constructor(canvasId: string) {
        const canvas = document.getElementById(canvasId) as HTMLCanvasElement;

        this.context = {
            canvas,
            ctx: canvas.getContext('2d')!,
            roomId: 0,
            playerId: 0,
            nickname: '',
            token: '',
            changeScene: (scene) => this.changeScene(scene),
            assets: {
                spritesheet: null,
                spritesData: null,
                board: null,
            },
        };

        loadAssets()
            .then((assets) => {
                this.context.assets.spritesheet = assets.spritesheet;
                this.context.assets.board = assets.board;
                this.context.assets.spritesData = assets.spritesData;
            })
            .then(() => this.initEvents())
            .then(() => this.restoreSession())
            .then(() => {
                this.isInitialized = true;
                requestAnimationFrame(() => this.gameLoop());
            })
            .catch((e) => console.error('Startup error:', e));
    }

    /** Próbuje wrócić do zapisanej sesji albo pokazuje ekran nicku. */
    private async restoreSession(): Promise<void> {
        const session = loadSession();

        if (!session) {
            this.changeScene(new NicknameScene());
            return;
        }

        const result = await reconnect(session);

        if (!result) {
            console.warn('Session expired or network error.');
            clearSession();
            this.changeScene(new NicknameScene());
            return;
        }

        this.context.roomId = session.roomId;
        this.context.playerId = session.playerId;
        this.context.token = session.token;
        this.context.nickname = session.nickname;

        this.changeScene(result.status === 'playing' ? new GameplayScene() : new LobbyScene());
    }

    /** Rejestruje zdarzenia myszy, klawiatury i zmiany rozmiaru okna. */
    private initEvents(): void {
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());

        const {canvas} = this.context;

        canvas.addEventListener('mousedown', (e) => {
            if (!this.currentScene) return;
            const rect = canvas.getBoundingClientRect();
            this.currentScene.onMouseDown(e.clientX - rect.left, e.clientY - rect.top);
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!this.currentScene?.onMouseMove) return;
            const rect = canvas.getBoundingClientRect();
            this.currentScene.onMouseMove(e.clientX - rect.left, e.clientY - rect.top);
        });

        window.addEventListener('keydown', (e) => {
            this.currentScene?.onKeyDown(e);
        });
    }

    /** Dopasowuje canvas do aktualnego okna przeglądarki. */
    private resizeCanvas(): void {
        this.context.canvas.width = window.innerWidth;
        this.context.canvas.height = window.innerHeight;
        this.currentScene?.onResize(this.context.canvas.width, this.context.canvas.height);
    }

    /** Niszczy poprzednią scenę i aktywuje nową. */
    private changeScene(scene: GameScene): void {
        this.currentScene?.onDestroy();
        this.currentScene = scene;
        scene.setContext(this.context);
        scene.onResize(this.context.canvas.width, this.context.canvas.height);
    }

    /** Wykonuje główną pętlę aktualizacji i renderowania. */
    private gameLoop(): void {
        if (this.isInitialized && this.currentScene) {
            this.currentScene.onUpdate();
            this.currentScene.onRender();
        }
        requestAnimationFrame(() => this.gameLoop());
    }
}

window.addEventListener('DOMContentLoaded', () => new GameApp('gameCanvas'));
