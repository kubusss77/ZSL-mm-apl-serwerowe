# Pędzące Żółwie

Prosta aplikacja gry przeglądarkowej z serwerem w PHP i klientem w TypeScript.

## Struktura

- `index.html` - wejście aplikacji w przeglądarce.
- `assets/` - grafiki planszy, sprite'y i dane sprite'ów.
- `server/` - część serwerowa PHP:
  - `Http/` - publiczne endpointy HTTP,
  - `Actions/` - obsługa akcji API,
  - `Domain/` - reguły gry, silnik zagrań i AI bota,
  - `Infrastructure/` - dostęp do Memcached,
  - `Support/` - pomocnicze moduły magazynu, czatu i workera bota.
- `client/src/` - źródła klienta TypeScript.
- `client/dist/` - wygenerowane pliki JavaScript używane przez `index.html`.
- `scripts/` - testy i narzędzia serwisowe.

## Wymagania

- PHP z rozszerzeniem `memcached`.
- Działający Memcached na `127.0.0.1:11211`.
- Node.js i npm do budowania klienta.

## Instalacja klienta

```bash
cd XXII/client
npm install
```

## Budowanie TypeScript

```bash
cd XXII/client
npm run build
```

Do pracy ciągłej:

```bash
cd XXII/client
npm run watch
```

## Uruchomienie aplikacji

Umieść katalog `XXII` na serwerze, a następnie otwórz:

```text
http://localhost/XXII/index.html
```

Endpointy HTTP są dostępne w `server/Http/`, np. `server/Http/api.php`.
