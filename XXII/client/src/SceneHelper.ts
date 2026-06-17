type TextParams = {
    context: CanvasRenderingContext2D;
    text: string;
    fillStyle?: string;
    fontWeight?: string;
    fontSize?: string;
    fontFamily?: string;
    textAlign?: CanvasTextAlign;
    x: number;
    y: number;
}

/** Rysuje tekst na canvasie z domyślnym stylem aplikacji. */
export const text = ({
                         context,
                         text,
                         textAlign = 'center',
                         fontWeight = "bold",
                         fontSize = "14px",
                         fontFamily = "'Comic Sans MS', Arial, sans-serif",
                         fillStyle = "#fff",
                         x,
                         y
                     }: TextParams) => {
    if (context.fillStyle !== null) {
        context.fillStyle = fillStyle!;
    }

    context.font = `${fontWeight} ${fontSize} ${fontFamily}`;

    context.textAlign = textAlign;
    context.fillText(text, x, y);
}
