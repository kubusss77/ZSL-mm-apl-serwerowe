# Librus

## Wymagania

- PHP (7.4 / 8.0+)
- Serwer HTTP (Apache, ServBay)
- Composer
- Rozszerzenia PHP: `curl`, `dom`, `libxml`

## Uruchamianie

1. Pobierz zależności:
   ```bash
   composer install
   ```
2. Sklonuj `secrets.php.example`, zmień nazwę na `secrets.php` i uzupełnij odpowiednimi wartościami
3. W pliku `.htaccess` zmień `AuthUserFile` na dokładną absolutną ścieżkę do wygenerowanego obok pliku `.htpasswd`.
   Aby zmienić hasło w `.htpasswd`, użyj:
   ```bash
   htpasswd -c /absolutna_sciezka/.htpasswd nowy_login
   ```
4. Dodaj wpisy z `crontab.txt` do crontaba z odpowiednimi ściezkami do plików

Domyślne kredki: admin admin
Jeśli coś nie działa poprawnie, usuń `cookie.txt` i powinno działać.
