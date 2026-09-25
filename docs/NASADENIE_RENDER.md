# Nasadenie na Render

Aplikácia sa na Render nasadzuje ako Docker kontajner (Render nemá natívnu podporu PHP).
V repozitári sú pripravené súbory:

| Súbor | Účel |
|---|---|
| `Dockerfile` | PHP 8.3, rozšírenia (pdo_sqlite, gd, zip, calendar), `composer install --no-dev` |
| `docker/start.sh` | pri štarte: kľúč aplikácie, migrácie, voliteľne testovacie dáta, server na porte `$PORT` |
| `render.yaml` | Render Blueprint – webová služba (plan free, vetva `main`, health check `/up`) |
| `.dockerignore` | do image nejdú `vendor`, `.env`, Excel matice, testy |

## Postup

1. **Kľúč aplikácie** – lokálne spustite `php artisan key:generate --show` a výsledok (začína `base64:`) si skopírujte.
2. Na [dashboard.render.com](https://dashboard.render.com) zvoľte **New → Blueprint** a vyberte repozitár `kasperek1ppzzs-ops/kapz-appka`.
   Render načíta `render.yaml` a vytvorí službu `kapz-appka`.
3. Pri vytváraní vyplňte premenné:
   - `APP_KEY` = kľúč z bodu 1,
   - `APP_URL` = adresa služby, napr. `https://kapz-appka.onrender.com`.
4. Po nasadení (prvý build trvá niekoľko minút) otvorte adresu služby a prihláste sa, napr. `novak@kapz.sk` / `password`.

**Kontrola:** `https://<služba>.onrender.com/up` musí vrátiť stav 200.

## Dôležité obmedzenia (free plán)

- **Dáta sa nezachovajú.** Databáza SQLite je vo vnútri kontajnera. Pri každom novom nasadení alebo reštarte
  služby vznikne nová databáza a pri `SEED_DEMO=true` sa do nej znova nahrajú testovacie dáta. Na skúšanie to stačí,
  na ostrú prevádzku nie.
- **Uspávanie.** Free služba sa po nečinnosti uspí; prvé otvorenie potom trvá dlhšie.
- **Server.** Aplikácia beží cez vstavaný PHP server (`php artisan serve`, 4 workery) – vhodné na testovanie,
  nie na záťaž desiatok súbežných používateľov.

## Pred ostrou prevádzkou

- Trvalá databáza: Render PostgreSQL (nastaviť `DB_CONNECTION=pgsql` a `DB_URL`) alebo MySQL podľa master promptu,
  prípadne platený plán s perzistentným diskom pre SQLite.
- `SEED_DEMO` nastaviť na `false` a vytvoriť skutočné účty (testovacie heslá sú verejne známe).
- Zvážiť výkonnejší webový server (PHP-FPM + Nginx/Apache alebo FrankenPHP).
