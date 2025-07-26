Ez egy modern, automatizált whitelist rendszer egy FiveM szerepjáték szerverhez. A célja, hogy biztosítsa, csak azok a játékosok léphessenek be a szerverre, akik ismerik és elfogadják a közösségi szabályokat.

A rendszer Discord bejelentkezést használ a felhasználók hitelesítésére. A jelentkezőknek egy online kvízt kell kitölteniük, amely a szerver szabályzatára vonatkozó kérdéseket tartalmaz. A sikeresen kitöltött teszteket az adminisztrátorok egy külön, jelszóval nem védett, hanem jogosultságalapú felületen bírálják el, ahol megtekinthetik a válaszokat is. A jóváhagyott játékosok automatikusan felkerülnek a szerver engedélyezési listájára.

A weboldal modern, reszponzív dizájnnal rendelkezik, és egy letisztult, felhasználóbarát felületen vezeti végig a jelentkezőket a folyamaton.


======================================================
PHP DISCORD WHITELIST RENDSZER - TELEPÍTÉSI ÚTMUTATÓ
======================================================

Üdvözlünk! Ez az útmutató segít beállítani a PHP alapú, Discord-azonosításon és kvízen alapuló whitelist rendszert. Az alábbi lépések végigvezetnek a teljes konfigurációs folyamaton.


------------------------------------
1. ALAPKONFIGURÁCIÓ (quiz.php)<img width="923" height="754" alt="Képernyőkép 2025-07-26 201049" src="https://github.com/user-attachments/assets/995eedec-b45a-4a4a-8286-0241e262ed83" />
<img width="538" height="364" alt="Képernyőkép 2025-07-26 201044" src="https://github.com/user-attachments/assets/20e9db2d-9713-4bfc-884d-05ef0f50b982" />
<img width="572" height="696" alt="Képernyőkép 2025-07-26 201034" src="https://github.com/user-attachments/assets/da141fdc-008d-40dd-af07-d2c3bc710359" />
<img width="1161" height="874" alt="Képernyőkép 2025-07-26 201027" src="https://github.com/user-attachments/assets/47a9570f-4e1f-4684-96e9-00e84e2e28da" />
<img width="1911" height="874" alt="Képernyőkép 2025-07-26 201017" src="https://github.com/user-attachments/assets/7b3de4fa-f903-4afa-8351-42bbb4248f2d" />
<img width="1915" height="882" alt="Képernyőkép 2025-07-26 201004" src="https://github.com/user-attachments/assets/dc1aab13-ee71-43ab-9d59-69caa9a391e9" />

------------------------------------
A rendszer működéséhez szükséges legfontosabb beállításokat a quiz.php fájlban kell elvégezned.

Discord Bot Beállítások:
Ezek az adatok kötik össze a weboldaladat a Discord alkalmazásoddal, amelyet a Discord Developer Portal (https://discord.com/developers/applications) oldalon kell létrehoznod.

define('DISCORD_CLIENT_ID', 'IDE_JÖN_A_DISCORD_CLIENT_ID'); // A Discord Developer Portálon létrehozott alkalmazásod "Client ID"-ja.
define('DISCORD_CLIENT_SECRET', 'IDE_JÖN_A_DISCORD_CLIENT_SECRET'); // Az alkalmazásod "Client Secret"-je. Ezt az adatot kezeld bizalmasan!
define('DISCORD_REDIRECT_URI', 'IDE_JÖN_A_TELJES_REDIRECT_URL'); // A weboldalad teljes URL-je. FONTOS: Ennek pontosan meg kell egyeznie a Discord Developer Portál OAuth2 beállításainál megadott Redirect URI-val! Példa: 'https://ateoldalad.hu/whitelist/index.php'
define('DISCORD_SERVER_INVITE', 'https://discord.gg/pelda'); // Egy végleges (soha le nem járó) meghívó link a Discord szerveredre.


------------------------------------------
2. KVÍZKÉRDÉSEK TESTRESZABÁSA (quiz.php)
------------------------------------------
A whitelist folyamat központi eleme a kvíz. A $questions tömbben adhatsz meg új kérdéseket, módosíthatod vagy törölheted a meglévőket.

A kérdések felépítése:
Minden kérdés három részből áll:
- question: Maga a kérdés, ami megjelenik a felhasználónak.
- options: A lehetséges válaszok. Itt a kulcs (pl. "A") a válasz betűjele, az érték pedig a megjelenő szöveg.
- answer: A helyes válasz betűjele az "options"-ből. A rendszer ezt használja az értékeléshez.

Példa:
$questions = [
    [
        "question" => "Mi a teendő, ha egy adminisztrátorral beszélsz?",
        "options" => ["A" => "Figyelmen kívül hagyom.", "B" => "Tisztelettudóan és érthetően kommunikálok.", "C" => "Vitatkozok vele."],
        "answer" => "B"
    ],
    [
        "question" => "Mi a 'VDM' (Vehicle Deathmatch) jelentése?",
        "options" => ["A" => "Barátságos dudálás.", "B" => "Egy másik játékos szándékos elütése járművel, megfelelő indok nélkül.", "C" => "Gyorshajtás a városban."],
        "answer" => "B"
    ],
];


-------------------------------------------
3. ADMINISZTRÁTORI HOZZÁFÉRÉS (quiz.php)
-------------------------------------------
Az $admin_discord_ids tömb segítségével adhatsz hozzáférést a rendszer adminisztrációs felületéhez.

* Hogyan működik? Ide sorold fel azoknak az adminisztrátoroknak a Discord Felhasználói Azonosítóját (ID), akiknek hozzáférést szeretnél adni.
* Discord ID megszerzése: A Discord kliensben engedélyezd a Fejlesztői módot (Beállítások -> Haladó). Ezután kattints jobb gombbal a kívánt felhasználó nevére, és válaszd a "Felhasználó azonosítójának másolása" opciót.

Beállítás:
$admin_discord_ids = [
    '123456789012345678', // Első admin Discord ID-ja
    '987654321098765432', // Második admin Discord ID-ja
];

Az adminisztrációs felület a következő linken érhető el: https://[TE-DOMAINED]/[MAPPA_HA_VAN]/admin.php


-----------------------------------
4. ADATKEZELÉS (whitelist.txt)
-----------------------------------
A rendszer nem használ hagyományos adatbázist (pl. MySQL). A sikeres és sikertelen whitelist-kérelmeket a whitelist.txt fájlba menti, amely a főmappában található. Ez a fájl szolgál a rendszer "adatbázisaként".


-----------------
5. SEGÍTSÉG
-----------------
Ha a beállítás során elakadsz vagy kérdésed van, csatlakozz a Discord szerverünkhöz:
https://discord.gg/43QuRqqUgV
