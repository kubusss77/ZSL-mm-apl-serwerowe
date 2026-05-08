## cz. 1

Konfiguracja wstępna:
$cookie_file_path = ""; // path do przechowywania ciasteczek 
$ch = curl_init();
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path); // "The name of the file containing the cookie data ..."
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // "Set CURLOPT_RETURNTRANSFER to TRUE to return the transfer as a string of the return value of curl_exec()"
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // "true to follow any "Location: " header that the server sends as part of the HTTP header."

Przykład wysłania zapytania GET'em:
$url=""; // URL
curl_setopt($ch, CURLOPT_URL, $url); // "The URL to fetch."
$res=curl_exec($ch);
print_r($res);

Przykład wysłania zapytania POST'em:
$url=""; // URL
$fields=Array(); // Array z danymi do wysłania
$POSTFIELDS = http_build_query($fields);
curl_setopt($ch, CURLOPT_POST, 1); // "true to do a regular HTTP POST."
curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS); // "The full data to post in a HTTP "POST" operation."
curl_setopt($ch, CURLOPT_URL, $url);
$res = curl_exec($ch);
print_r($res);

Zadanie:
Uywając cURL'a (w PHP) zaloguj się na konto uczniowskie w Librusie i wyświetl zawartość zakładki "Oceny ucznia".
Pozdbądź się sortowania i zachowaj css'owe formatowanie.

Hinty do logowania (cURL):

- w cURL ustaw plik cookie dowolony np "cookie.txt" (nie musi istnieć a strona musi cookie "wspierać" ;) )
- otwórz konsolę -> Sieć (u mnie FireFox)
- zaznacz opcję "Trwałe dzienniki"

- otwórz Librus'a
- przejdź do formularza logowania
- zaloguj się na swoje konto Librus
- ustaw filtry na "HTML" i "XHR"

- logowanie jest oparte na zaznaczonych zapytaniach:
  1.  Wysłane dane logowania w odpowiedzi JSON:
      {"status":"ok","goTo":"\/OAuth\/Authorization\/2FA?client_id=46"}
  2.  To URL "goTo" z 1. W odpowiedzi (sprawdź odpowiedź "Nieprzetworzoną") wraca scrypt JS(!)
      w nim PostMessaging (wysłanie posta):

      [...]
      window.parent.postMessage({
      "command":"open_synergia_window",
      "commandPayload":{
      "url":"https:\/\/synergia.librus.pl\/uczen\/index"}
      }, [...]

      UWAGA: nie wysyłaj tego zapytania tylko poniższe

  3.Wyślij powyższe dane POST (z pkt.2) na adres "goTo"

- jeśli się udało zalogować wyślij GET do https://synergia.librus.pl/przegladaj_oceny/uczen
- powinieneś/nnaś uzyskać stronę z ocenami

## cz. 2

W projekcie scal obie tabele z ocenami w jedną zbiorczą. Poniżej popożądany efekt:

- zachowaj funkcjonalności rozwijania (pojedynczy wiersz / wszystkie) plusem
- zachowaj naprzemienną kolorystykę wierszy

Hinty:

1. Użyłem XPath:
   - do XPatha możesz skorzystać z simplexml (t.S.XI)
   - simplexml_load_file w parametrze przyjmuje XML. Jak będzie ok to print_r wyświetli pobraną strukturę.
   - musisz tak modyfikować/przygotować kod by go (XML) uzyskać. Polecam validatory XML online np. ten
   - do "wyrównania" naszego HTML'a do standardu użyłem htmLawed
2. Użyłem RegExp (t.S.VIII)
   Może się przydać dodatkowa wiedza z wyrażeń: - wyłączania grup przechwytujących ?: (optymalizacja) - spogladania "przed siebie i za siebie" (link j.w)
3. Nie użyłem a można DomDocument i np. load/saveHTML(), getElementBy itp.
   Polecam, bowiem odpada problem z "dociąganiem" do XML. jak w p.1

   Przykładowy kod "wycinający" element ze strony (stringa) genealodzy.pl (u nas tekst z librusa):

<?php
$html = file_get_contents("http://genealodzy.pl");

$doc = new DOMDocument();
// https://www.php.net/manual/en/class.domdocument.php

@$doc->loadHTML($html);

//$html = $doc->saveHTML();
//echo $html;

$xpath = new DOMXPath($doc);
$elements = $xpath->query("//a");
$element = $elements->item(0);

echo $doc->saveHTML($element);

W projekcie:
	- dodaj wysyłanie maila z infomacją o aktualnym szczęśliwym numerku.
	- maila wysyłaj od pn-pt o określonej godzinie

Wkorzystaj:
	- jako scheduler cron'a
	- dowolnej biblioteki PHP do wysyłania mili (nie funkcją mail)

Użyteczna wiedza:
Harmonogram 
	cron

	test w linuxie:
	plik np. crontab
		* * * * * /bin/echo "test" >> /folder/gdzie/zapiszemy/test.txt

	polecenie: 
		crontab ./crontab   <- nadpisuje harmonogram
		crontab -l 	<- aktualny harmonogram
		crontab -d	<- usunięcie harmonogramu

Skrypt wysyłający maile z danej "chwili" wywołujemy np. za pomocą 
		lynx z opcją --source
	
Każde z powyższych rozwiązań w zależności od środowiska ma swoje umiejscowienie:
		- na hostingach bez dostępu do powłoki szukaj w CPanel'u lub DirectAdmini'e
		- pod Windowsem przyda się np. XAMPP, nnCron i lynx

## cz. 3

W projekcie:
	- dodaj wysyłanie maila z infomacją o aktualnym szczęśliwym numerku.
	- maila wysyłaj od pn-pt o określonej godzinie

Wkorzystaj:
	- jako scheduler cron'a
	- dowolnej biblioteki PHP do wysyłania mili (nie funkcją mail)

Użyteczna wiedza:
Harmonogram 
	cron

	test w linuxie:
	plik np. crontab
		* * * * * /bin/echo "test" >> /folder/gdzie/zapiszemy/test.txt

	polecenie: 
		crontab ./crontab   <- nadpisuje harmonogram
		crontab -l 	<- aktualny harmonogram
		crontab -d	<- usunięcie harmonogramu

Skrypt wysyłający maile z danej "chwili" wywołujemy np. za pomocą 
		lynx z opcją --source
	
Każde z powyższych rozwiązań w zależności od środowiska ma swoje umiejscowienie:
		- na hostingach bez dostępu do powłoki szukaj w CPanel'u lub DirectAdmini'e
		- pod Windowsem przyda się np. XAMPP, nnCron i lynx

## cz. 4

W projekcie dodaj średnie

## cz. 5

Projekt umieść w strefie za hasłem. Użyj logowania opartego o htpasswd.
Jeśli zrobiłeś/aś temat z schedulerem zadbaj by nadal działał (plik wysyłający w strefie za hasłem).

Użyteczna wiedza:
Strefa na hasło:
	htaccess + htpasswd

Rozwiązanie w zależności od środowiska ma swoje umiejscowienie:
	- na hostingach bez dostępu do powłoki szukaj w CPanel'u lub DirectAdmini'e
	- pod Windowsem przyda się XAMPP, generator haseł.

## cz. 6

W projekcie dodaj możliwość wygenerowania pliku PDF o następującej konstrukcji:
	
	

	- wykorzystaj bibliotekę TCPDF
	- wypełnij pola brakującymi średnimi/ocenami
	- w przypadku ocen z wagą umieść ją koło oceny w nawiasie okrągłym np. 3 (3)
	- w przypadku ocen punktowych po sleszu podaj bazę np. 5/10
	- w prawym górnym rogu nagłówka umieść dane dot. czasu wygenerowania PDF'a (format jak na zdjęciu)
	- umożliw wybór orientacji (pion/poziom) generowanego dokumentu - odpowiednio dostosuj szerokość tabeli/kolumn (wykorzystanie zyskanego miejsca)
	- w przypadku przepełnienia komórki wiersz powinien się poszerzyć


	Polecam:
		Header and Footer (AddPage, SetFont, SetHeaderMargin)
		Cell
		Multicell (SetFillColor, setCellPaddings)
		setXY
		getX / getY

	Jeśli przytłacza Cię dokumentacja TCPDF to wspomagaj się dokumentacją z "poprzednika" - FPDF
