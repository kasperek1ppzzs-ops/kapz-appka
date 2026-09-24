# ANTIGRAVITY MASTER PROMPT – Webová aplikácia pre povinnú a podpornú dokumentáciu KAPZ/APZ

## 1. Úloha projektu

Tvojou úlohou je navrhnúť a implementovať webovú aplikáciu, ktorá nahradí existujúcu Excel maticu `8 matica 2026.xlsm` používanú na zapisovanie povinnej a podpornej dokumentácie KAPZ a APZ.

Aplikácia nesmie byť iba „Excel v prehliadači“. Má ísť o centralizovaný informačný systém s databázou, autentifikáciou, rolami, workflow, dashboardom, reportovaním a generovaním PDF dokumentov.

Excel matica je **referenčný model procesov, výpočtov, polí, väzieb a finálneho vzhľadu oficiálnych dokumentov**.

---

## 2. Povinné technológie

### Backend
- **PHP** je povinný.
- Nepoužívaj Node.js, Python, Java, .NET ani inú technológiu ako hlavný backend.
- Preferovaný framework: **Laravel**, pokiaľ nie je technický dôvod použiť inú PHP architektúru.

### Databáza
- **MySQL** je povinná relačná databáza.
- Dáta musia byť uložené normalizovane a relačne.
- Nepoužívaj Excel ako databázu.
- Nepoužívaj SQLite ako produkčnú databázu.

### Frontend
- Moderné responzívne webové rozhranie.
- Desktop je hlavný pracovný režim, ale aplikácia musí byť použiteľná aj na tablete.
- Používateľské rozhranie KAPZ nesmie pôsobiť ako administrátorský panel.
- Administrátorská časť môže používať vhodný PHP/Laravel admin framework.

### Výstupy
- PDF dokumenty.
- Exporty/reporty do XLSX alebo CSV tam, kde to dáva zmysel.

---

## 3. Najdôležitejšie pravidlo

**Oficiálna dokumentácia musí po vygenerovaní zodpovedať Excel matici čo najpresnejšie.**

Týka sa to najmä:

- EVIDENCIA_KAPZ,
- EVIDENCIA_APZ,
- KNIHY,
- Plán pracovných ciest,
- Cestovný príkaz,
- Správa z pracovnej cesty,
- Vyúčtovanie,
- PREHLÁSENIE,
- ďalších dokumentov, ktoré sa v priebehu analýzy identifikujú ako povinné alebo používané.

Neoptimalizuj svojvoľne ich finálny vizuálny formát. Webový formulár môže byť modernejší a používateľsky prívetivejší, ale výsledný PDF dokument musí rešpektovať existujúcu podobu, obsah, poradie údajov a logiku Excel matice.

---

## 4. Excel matica ako zdroj pravdy pre dokumenty

Pri každom spustení práce na projekte:

1. skontroluj, či je v projekte dostupný súbor `8 matica 2026.xlsm`,
2. preštuduj jeho aktuálnu štruktúru,
3. analyzuj hárky, vzorce, väzby, vstupné polia a výstupy,
4. makrá/VBA ignoruj, pokiaľ nebudú výslovne požadované,
5. nevychádzaj iba zo starších poznámok, ak sa Excel medzitým zmenil,
6. pri rozpore medzi starou dokumentáciou projektu a aktuálnou Excel maticou upozorni na rozdiel pred implementáciou.

Excel je referenčný model funkčnosti a výstupných dokumentov, nie produkčná databáza.

---

## 5. Hlavný používateľ systému

Hlavným používateľom systému je **KAPZ**.

KAPZ:

- vypĺňa dokumentáciu za seba,
- vypĺňa dokumentáciu za svojich pridelených APZ,
- pracuje s dochádzkou,
- pripravuje plán pracovných ciest,
- vypĺňa cestovné príkazy,
- vypĺňa správy z pracovných ciest,
- vypĺňa vyúčtovania,
- pracuje s ďalšou povinnou a podpornou dokumentáciou.

Cieľom aplikácie je minimalizovať duplicitné zadávanie údajov.

**Každý údaj sa má, pokiaľ je to možné, zadať iba raz a následne sa má používať vo všetkých súvisiacich dokumentoch.**

---

## 6. Jedno CMS pre všetkých KAPZ

Systém bude tvoriť **jedna webová aplikácia / jedno CMS**.

Nie 33 samostatných aplikácií.

V systéme je približne **33 KAPZ**.

Po prihlásení musí systém podľa identity používateľa automaticky načítať:

- údaje konkrétneho KAPZ,
- jeho osobné číslo,
- jeho pôsobnosť,
- jeho relevantné pracovné údaje,
- jeho pridelených APZ,
- osobné čísla APZ,
- pôsobnosti APZ,
- historické a aktuálne dokumenty patriace tomuto KAPZ a jeho APZ.

KAPZ nesmie vidieť dáta iného KAPZ, pokiaľ na to nemá špeciálne oprávnenie.

---

## 7. Centrálna databáza

V systéme bude jedna centrálna MySQL databáza.

Administrátor bude spravovať najmä:

- zoznam KAPZ,
- zoznam APZ,
- osobné čísla,
- pôsobnosti,
- pracovné pozície,
- priradenia APZ ku KAPZ,
- obdobia platnosti priradení,
- stav používateľských účtov,
- systémové číselníky,
- limity,
- kalendár,
- sviatky,
- typy neprítomností,
- ďalšie centrálne údaje používané dokumentáciou.

KAPZ nemá manuálne vytvárať vlastný zoznam APZ. Systém mu jeho APZ načíta z centrálnej databázy.

---

## 8. História priradení APZ ku KAPZ

Priradenie APZ ku KAPZ nesmie byť uložené iba ako jedna aktuálna hodnota.

Použi historizované priradenia, napríklad:

```text
APZ: Ján Novák
01.01.2026 – 31.08.2026 → KAPZ A
01.09.2026 –             → KAPZ B
```

Dôvod:

Ak sa APZ presunie pod iného KAPZ, stará dokumentácia sa nesmie spätne presunúť pod nového KAPZ.

Report za konkrétne obdobie musí používať priradenie platné v danom období.

História priradení je databázová logika. Bežný KAPZ ju nemusí riešiť.

---

## 9. Roly a oprávnenia

### 9.1 Administrátor

Administrátor má plnú kontrolu nad systémom.

Môže:

- spravovať všetkých KAPZ,
- spravovať všetkých APZ,
- meniť priradenia,
- spravovať číselníky,
- spravovať používateľské účty,
- vidieť všetku dokumentáciu,
- vidieť všetky reporty,
- kontrolovať plnenie povinností,
- schvaľovať plány pracovných ciest,
- vracať dokumenty na doplnenie,
- zamietať dokumenty tam, kde to workflow povoľuje,
- vidieť auditnú stopu,
- exportovať reporty,
- prípadne odomykať uzavreté obdobia alebo dokumenty podľa oprávnení.

### 9.2 KAPZ

KAPZ môže pracovať iba s:

- vlastnými údajmi,
- vlastnou dokumentáciou,
- aktuálne alebo historicky pridelenými APZ podľa relevantného obdobia,
- dokumentáciou svojich APZ,
- vlastnými pracovnými cestami,
- vlastným plánom pracovných ciest.

### 9.3 Budúce roly

Architektúra musí umožniť neskôr doplniť napríklad:

- regionálneho používateľa,
- kontrolóra,
- read-only používateľa,
- manažment.

Tieto roly teraz nevytváraj bez potreby, ale návrh databázy a autorizácie ich nesmie znemožniť.

---

## 10. Hlavný dashboard KAPZ

Dashboard KAPZ musí byť pracovný, nie iba dekoratívny.

Má okamžite odpovedať na otázky:

- Čo mám za aktuálny mesiac hotové?
- Čo ešte nemám hotové?
- Čo chýba pri mojich APZ?
- Ktorý dokument je rozpracovaný?
- Ktorý dokument čaká na schválenie?
- Ktorý dokument mi administrátor vrátil?
- Čo má termín alebo je po termíne?

Príklady widgetov:

- stav mesačnej dokumentácie,
- stav dochádzky,
- počet APZ s kompletnou/nekompletnou dokumentáciou,
- plán pracovných ciest,
- čakajúce úlohy,
- pracovné cesty,
- chýbajúce správy,
- chýbajúce vyúčtovania,
- upozornenia.

Dashboard musí umožniť preklik do konkrétneho problému.

---

## 11. Administrátorský dashboard

Administrátor musí mať centrálny pohľad na všetkých približne 33 KAPZ.

Dashboard má minimálne zobrazovať:

- kto má dokumentáciu kompletnú,
- kto ju má rozpracovanú,
- kto ju nemá odovzdanú,
- kto je po termíne,
- ktoré plány pracovných ciest čakajú na schválenie,
- ktoré plány boli schválené,
- ktoré boli vrátené na doplnenie,
- ktoré ešte neboli odoslané,
- stav evidencie KAPZ,
- stav evidencie APZ,
- stav pracovných ciest,
- stav správ,
- stav vyúčtovaní,
- stav ďalších povinných dokumentov.

Administrátor musí vedieť kliknúť z agregovaného čísla až na konkrétneho KAPZ a konkrétny chýbajúci dokument.

---

## 12. Reporting

Reporting je jedna z kľúčových funkcií systému.

### Operatívny reporting

Musí vedieť zobraziť napríklad:

- KAPZ s nekompletnou dokumentáciou,
- KAPZ s neuzavretou dochádzkou,
- APZ s chýbajúcou evidenciou,
- neodoslané plány pracovných ciest,
- plány čakajúce na schválenie,
- chýbajúce správy z pracovných ciest,
- chýbajúce vyúčtovania,
- dokumenty po termíne.

### Manažérsky reporting

Priprav architektúru pre reporty ako:

- počet pracovných ciest,
- počet kilometrov,
- čerpanie kilometrových limitov,
- dochádzka,
- PN,
- OČR,
- dovolenky,
- ostatné neprítomnosti,
- stav dokumentácie,
- porovnanie období,
- porovnanie KAPZ,
- prehľady podľa pôsobností.

### Filtre

Reporty majú podporovať minimálne:

- mesiac,
- rok,
- obdobie od-do,
- KAPZ,
- APZ,
- pôsobnosť,
- typ dokumentu,
- stav dokumentu.

---

## 13. Workflow Plánu pracovných ciest

Plán pracovných ciest podlieha schváleniu administrátorom.

Minimálne stavy:

```text
ROZPRACOVANÝ
    ↓
ODOSLANÝ NA SCHVÁLENIE
    ↓
    ├── SCHVÁLENÝ
    ├── VRÁTENÝ NA DOPLNENIE
    └── ZAMIETNUTÝ
```

Pri každej zmene stavu eviduj:

- autora,
- čas vytvorenia,
- čas odoslania,
- kto rozhodol,
- čas rozhodnutia,
- poznámku administrátora,
- verziu dokumentu.

Po schválení nesmie byť plán voľne editovateľný.

Ak je potrebná zmena:

- vytvor novú verziu,
- alebo použi explicitné odomknutie administrátorom,
- zmenu audituj,
- podľa potreby vyžaduj nové schválenie.

---

## 14. Evidencia KAPZ a evidencia APZ

Toto je kritická časť projektu.

### EVIDENCIA_KAPZ

Musí byť funkčne a výstupne **presne reprodukovaná podľa Excel matice**.

Zachovaj:

- všetky relevantné polia,
- logiku dní,
- stavy dochádzky,
- pracovné hodiny,
- súčty,
- názvy,
- poradie údajov,
- výpočty,
- výslednú podobu dokumentu.

### EVIDENCIA_APZ

Rovnako musí byť **presne reprodukovaná podľa matice**.

KAPZ vypĺňa evidenciu za svojich APZ.

Systém musí podľa prihláseného KAPZ automaticky načítať iba jeho APZ.

### PDF

Po vyplnení/uzavretí obdobia musí byť možné vygenerovať PDF v podobe zodpovedajúcej Excel matici.

Nevytváraj približnú „modernú alternatívu“ výsledného dokumentu.

---

## 15. KNIHY

KNIHY musia zostať obsahovo a vizuálne zhodné s Excel maticou.

Webová aplikácia môže údaje získavať automaticky z databázy a evidencie dochádzky, ale výsledná kniha musí reprodukovať existujúcu Excel predlohu.

Nevyžaduj duplicitné zadávanie údajov, ktoré už systém pozná.

---

## 16. Plán pracovných ciest

Plán pracovných ciest musí:

- obsahovo zodpovedať Excel matici,
- vizuálne zodpovedať Excel predlohe pri PDF výstupe,
- používať centrálne údaje KAPZ,
- podporovať mesačné obdobia,
- podporovať schvaľovací workflow,
- evidovať verzie,
- evidovať stav schválenia,
- byť dostupný administrátorovi na kontrolu a schválenie.

---

## 17. Cestovný príkaz

Cestovný príkaz musí byť reprodukovaný podľa Excel matice.

Systém má automaticky predvyplniť všetko, čo už pozná, napríklad:

- KAPZ,
- osobné číslo,
- pôsobnosť,
- relevantné údaje pracovnej cesty,
- údaje z plánu pracovných ciest, ak sú použiteľné.

Výsledný PDF dokument musí zodpovedať predlohe v matici.

---

## 18. Správa z pracovnej cesty

Správa z pracovnej cesty musí byť reprodukovaná podľa Excel matice.

Ak existuje nadväznosť na cestovný príkaz, systém má údaje preniesť automaticky.

Nevyžaduj od KAPZ opätovné vypĺňanie údajov, ktoré už zadal pri pracovnej ceste alebo cestovnom príkaze.

PDF musí zodpovedať Excel predlohe.

---

## 19. Vyúčtovanie

Vyúčtovanie musí byť reprodukované podľa Excel matice.

Použi automatický prenos dostupných údajov z:

- pracovnej cesty,
- cestovného príkazu,
- centrálnej databázy,
- prípadných ďalších súvisiacich záznamov.

Výpočty musia byť konzistentné s Excel maticou.

PDF musí zodpovedať Excel predlohe.

---

## 20. PREHLÁSENIE

PREHLÁSENIE musí zostať obsahovo a výstupne zhodné s aktuálnou používanou Excel predlohou.

Údaje o KAPZ/APZ načítavaj z centrálnej databázy a z relevantných aktuálnych údajov.

---

## 21. HLASENIE

V Excel matici je HLASENIE významný zdroj údajov.

Vo webovej aplikácii však jeho úlohu vo veľkej miere preberie:

- centrálna MySQL databáza,
- mesačné záznamy dochádzky,
- priradenia APZ,
- číselníky,
- pracovné obdobia.

Nevytváraj automaticky samostatnú webovú obrazovku s názvom HLASENIE iba preto, že existuje Excel hárok.

Najprv identifikuj, akú funkciu konkrétny údaj plní, a ulož ho do správnej databázovej entity.

---

## 22. Nepoužívané / historické hárky

Nasledujúce hárky sa nemajú považovať za aktívne pracovné moduly webovej aplikácie:

### HODNOTENIE_APZ

- už sa nepoužíva ako pracovný hárok,
- ide o skrytý/historický zdroj údajov,
- jeho údaje majú byť vo webovej aplikácii získavané z centrálnej databázy alebo logiky zodpovedajúcej HLASENIE,
- nevytváraj samostatný modul HODNOTENIE_APZ, pokiaľ nebude výslovne požadovaný.

### PV_KAPZ1

- nepotrebný,
- nevytváraj ako samostatný modul.

### Hárok1

- nepotrebný,
- nevytváraj ako samostatný modul.

Pred odstránením akejkoľvek funkcionality však vždy skontroluj, či Excel nepoužíva údaje z týchto hárkov nepriamo pre aktívny dokument. Potrebnú logiku prenes do správneho nového modulu alebo databázovej vrstvy.

---

## 23. Správa o pracovnej činnosti

Ak sa tento dokument bude vo webovej aplikácii používať, nesmie byť závislý od historického hárku HODNOTENIE_APZ.

Údaje získavaj priamo z:

- centrálnej databázy,
- HLASENIE logiky prenesenej do databázy,
- evidencie dochádzky,
- pracovných ciest,
- relevantných formulárov.

Ak dokument zostáva súčasťou povinnej/podpornej dokumentácie, jeho finálny PDF výstup musí zodpovedať Excel predlohe.

---

## 24. Auditná stopa

Systém musí auditovať dôležité zmeny.

Minimálne eviduj:

- kto záznam vytvoril,
- kto ho upravil,
- kedy bol vytvorený,
- kedy bol upravený,
- pôvodnú hodnotu,
- novú hodnotu,
- zmenu stavu dokumentu,
- schválenie,
- zamietnutie,
- vrátenie na doplnenie,
- odomknutie dokumentu.

Audit log nesmie byť bežne editovateľný používateľom.

---

## 25. Stavový model dokumentov

Dokumenty, pri ktorých je to relevantné, majú podporovať stavy, napríklad:

```text
ROZPRACOVANÝ
KOMPLETNÝ
ODOSLANÝ
ČAKÁ NA SCHVÁLENIE
SCHVÁLENÝ
VRÁTENÝ NA DOPLNENIE
ZAMIETNUTÝ
UZAVRETÝ
```

Nepoužívaj všetky stavy bezhlavo na každý dokument.

Pre každý typ dokumentu definuj iba stavy, ktoré zodpovedajú reálnemu procesu.

---

## 26. Uzatváranie období

Navrhni systém tak, aby bolo možné uzavrieť mesačné obdobie.

Po uzavretí:

- dokumenty nemajú byť voľne editovateľné,
- administrátor môže mať oprávnenie obdobie odomknúť,
- odomknutie musí byť auditované,
- historické PDF musí zostať dohľadateľné.

Presné pravidlá uzatvárania implementuj až po potvrdení workflow.

---

## 27. PDF a verzovanie

Pri dokumentoch, ktoré sa generujú do PDF:

- uchovaj informáciu o verzii,
- uchovaj čas vygenerovania,
- uchovaj autora,
- rozlišuj pracovný náhľad a finálnu verziu, ak je to potrebné,
- po schválení alebo uzavretí musí byť možné spätne zobraziť presne tú verziu, ktorá bola schválená/uzavretá.

Nevytváraj nové PDF dynamicky z aktuálne zmenených dát, ak používateľ žiada historicky schválenú verziu dokumentu.

---

## 28. Validácie

Serverová validácia je povinná.

Frontendová validácia je iba doplnok.

Kontroluj napríklad:

- povinné polia,
- dátumy,
- logické intervaly,
- pracovné hodiny,
- duplikované záznamy,
- príslušnosť APZ ku KAPZ v danom období,
- stav dokumentu,
- oprávnenie používateľa,
- konzistenciu nadväzujúcich pracovných ciest.

---

## 29. Bezpečnosť

Implementuj minimálne:

- bezpečné prihlasovanie,
- hashovanie hesiel,
- CSRF ochranu,
- ochranu proti SQL injection,
- server-side autorizáciu,
- ochranu IDOR,
- session security,
- rate limiting tam, kde je vhodné,
- audit administrátorských zásahov,
- bezpečné generovanie a ukladanie dokumentov,
- kontrolu prístupu k PDF a exportom.

Nikdy sa nespoliehaj iba na skrytie tlačidla vo frontende.

Každé oprávnenie musí byť kontrolované aj na serveri.

---

## 30. Databázový návrh

Nevytváraj jednu obrovskú tabuľku kopírujúcu Excel.

Použi normalizovaný relačný model.

Očakávané entity môžu zahŕňať napríklad:

```text
users
roles
permissions

kapz
apz
apz_assignments
locations
work_positions

reporting_periods
attendance_entries
attendance_types

travel_plans
travel_plan_items
business_trips
travel_orders
travel_expenses
travel_reports

activity_reports
statements

monthly_limits
holidays

pdf_documents
document_versions
approval_actions
notifications
audit_logs
```

Toto nie je finálna schéma.

Pred vytvorením migrácií ju uprav podľa skutočnej analýzy Excel matice.

---

## 31. Dátové pravidlo: jeden údaj, jedno miesto

Ak systém pozná:

- meno KAPZ,
- osobné číslo,
- pôsobnosť,
- meno APZ,
- pracovnú pozíciu,
- dátum pracovnej cesty,
- trasu,
- alebo iný centrálny údaj,

nevyžaduj jeho opätovné ručné zadanie v každom dokumente.

Dokument má zobrazovať alebo použiť údaje z databázy.

Ak je potrebné uchovať historický stav hodnoty, urob snapshot do dokumentu/verzie v okamihu uzavretia alebo schválenia.

---

## 32. Notifikácie

Priprav systém pre notifikácie, napríklad:

### KAPZ
- plán bol schválený,
- plán bol vrátený,
- plán bol zamietnutý,
- chýba dokument,
- blíži sa termín,
- dokument je po termíne.

### Administrátor
- nový plán čaká na schválenie,
- KAPZ neodovzdal dokumentáciu,
- vznikla výnimka alebo chyba,
- dokument bol znovu odoslaný po doplnení.

Začni internými notifikáciami v aplikácii.

Emailové notifikácie priprav architektonicky, ale aktivuj až podľa požiadaviek.

---

## 33. UX zásady

Aplikácia má byť pre KAPZ jednoduchšia než Excel.

Preferuj:

- predvyplnené údaje,
- dropdowny namiesto voľného textu tam, kde existuje číselník,
- hromadné úpravy dochádzky,
- jasný stav dokumentov,
- vizuálne upozornenie na chyby,
- automatické výpočty,
- automatické prenosy údajov medzi dokumentmi,
- minimálny počet kliknutí,
- návrat na rozpracovaný dokument,
- autosave tam, kde je bezpečný a predvídateľný.

Nepoužívaj pre KAPZ technické názvy databázových tabuliek alebo interných objektov.

---

## 34. Postup implementácie

Neimplementuj celý systém naraz bez analýzy.

Postupuj po moduloch.

### Fáza 1 – analýza

- analyzuj Excel,
- vytvor mapu hárkov,
- identifikuj vstupné polia,
- identifikuj odvodené hodnoty,
- identifikuj dokumenty,
- identifikuj presné väzby,
- identifikuj duplicity,
- identifikuj historické/nepoužívané hárky.

### Fáza 2 – dátový model

- ERD,
- tabuľky,
- cudzie kľúče,
- historizácia,
- číselníky,
- oprávnenia.

### Fáza 3 – autentifikácia a centrálna databáza

- admin,
- KAPZ,
- APZ,
- pôsobnosti,
- priradenia.

### Fáza 4 – dochádzka

- EVIDENCIA_KAPZ,
- EVIDENCIA_APZ,
- presné výpočty,
- PDF.

### Fáza 5 – KNIHY

- automatické generovanie z evidencie,
- presný PDF výstup.

### Fáza 6 – pracovné cesty

- plán,
- schvaľovanie,
- cestovný príkaz,
- správa,
- vyúčtovanie.

### Fáza 7 – ďalšie dokumenty

- PREHLÁSENIE,
- Správa o pracovnej činnosti,
- ďalšie potvrdené dokumenty.

### Fáza 8 – dashboard a reporting

- KAPZ dashboard,
- admin dashboard,
- drill-down,
- exporty,
- notifikácie.

### Fáza 9 – import starších údajov

- priprav kontrolovaný import z existujúcich Excel súborov,
- validuj dáta,
- nevkladaj historické údaje bez kontroly konzistencie.

---

## 35. Testovanie

Každý modul musí mať testy.

Minimálne testuj:

- oprávnenia KAPZ,
- oddelenie dát medzi KAPZ,
- administrátorské oprávnenia,
- priradenia APZ podľa obdobia,
- dochádzkové výpočty,
- stavy neprítomností,
- výpočty pracovných ciest,
- schvaľovací workflow,
- zablokovanie editácie po schválení,
- audit log,
- generovanie PDF,
- zhodu kľúčových hodnôt s Excel maticou.

Pri kritických výpočtoch vytvor porovnávacie testy:

```text
rovnaký vstup v Exceli
vs.
rovnaký vstup vo webovej aplikácii
```

Výsledok musí byť zhodný.

---

## 36. Pixelová / obsahová kontrola PDF

Pri migrácii oficiálnych dokumentov:

1. vytvor referenčný dokument z Excel matice,
2. vytvor rovnaký dokument z webovej aplikácie,
3. porovnaj:
   - názvy,
   - poradie polí,
   - formát dátumov,
   - výpočty,
   - tabuľky,
   - šírky/stĺpce,
   - hlavičky,
   - päty,
   - zalomenia strán,
   - podpisové časti,
   - celkový vizuál,
4. odchýlky oprav pred označením modulu za hotový.

---

## 37. Zdrojové súbory a dokumentácia projektu

V repozitári udržiavaj minimálne:

```text
README.md
AGENTS.md
PRODUCT_SPEC.md
DATABASE_SCHEMA.md
WORKFLOWS.md
EXCEL_MAPPING.md
REPORTING_SPEC.md
PDF_TEMPLATES.md
CHANGELOG.md
```

### EXCEL_MAPPING.md

Pre každý aktívny Excel hárok/document uveď:

- pôvodný názov hárka,
- účel,
- kto ho vypĺňa,
- vstupné bunky/polia,
- automatické hodnoty,
- zdroj dát,
- cieľová databázová entita,
- cieľový webový modul,
- PDF šablóna,
- známe výpočty,
- závislosti.

---

## 38. Pravidlá práce Antigravity

Pri každej väčšej zmene:

1. najprv si preštuduj aktuálny stav projektu,
2. prečítaj `AGENTS.md` a tento master prompt,
3. skontroluj relevantné časti Excel matice,
4. nájdi existujúce implementácie a nevytváraj duplicitu,
5. popíš dopad zmeny na databázu, workflow a PDF,
6. implementuj zmenu,
7. spusti testy,
8. oprav regresie,
9. aktualizuj dokumentáciu,
10. zapíš významnú zmenu do changelogu.

Nevykonávaj rozsiahle refaktory bez dôvodu.

Nevymieňaj technologický stack bez výslovného povolenia.

---

## 39. Zakázané skratky

Nerob tieto chyby:

- nevytváraj jednu webovú stránku pre každý Excel hárok bez analýzy procesu,
- nevytváraj 33 samostatných databáz alebo inštalácií pre 33 KAPZ,
- neukladaj dáta primárne do Excel súborov,
- nepoužívaj Node/Python ako backend namiesto PHP,
- nepoužívaj inú databázu namiesto MySQL bez súhlasu,
- nevyžaduj duplicitné zadávanie údajov,
- nemeň vzhľad oficiálnych dokumentov podľa vlastného uváženia,
- neignoruj historické priradenia APZ,
- nedovoľ KAPZ čítať dáta iného KAPZ,
- nedovoľ meniť schválené dokumenty bez auditovaného workflow,
- nespoliehaj sa iba na frontendové oprávnenia,
- neimplementuj HODNOTENIE_APZ, PV_KAPZ1 alebo Hárok1 ako nové aktívne moduly bez explicitnej požiadavky.

---

## 40. Definícia úspechu

Projekt je úspešný, keď:

1. existuje jedno centrálne PHP/MySQL CMS,
2. všetkých približne 33 KAPZ používa tú istú aplikáciu,
3. po prihlásení každý KAPZ vidí iba seba a svojich APZ,
4. administrátor spravuje celú centrálnu databázu,
5. údaje sa nezadávajú zbytočne opakovane,
6. povinné dokumenty sa generujú v podobe zodpovedajúcej Excel matici,
7. EVIDENCIA_KAPZ a EVIDENCIA_APZ sú funkčne zhodné s maticou,
8. KNIHY sú funkčne a výstupne zhodné s maticou,
9. plán pracovných ciest podlieha administrátorskému schváleniu,
10. cestovné príkazy, správy, vyúčtovania a prehlásenia zodpovedajú predlohám,
11. administrátor má kvalitný dashboard a reporting,
12. systém ukáže, kto má čo hotové a čo chýba,
13. všetky dôležité zmeny sú auditované,
14. historické dokumenty a priradenia zostávajú správne aj po personálnych zmenách,
15. aplikácia je používateľsky jednoduchšia než dnešný Excel.

---

# Záverečný pokyn

Pred naprogramovaním konkrétneho modulu vždy najprv pochop jeho aktuálnu funkciu v Excel matici.

**Excel neurčuje architektúru aplikácie, ale určuje procesnú logiku, povinné údaje, výpočty a finálnu podobu dokumentov.**

Nová webová aplikácia má odstrániť technologické obmedzenia Excelu, nie meniť schválenú dokumentáciu bez požiadavky používateľa.

Technologický základ projektu je záväzný:

> **PHP + MySQL + jedno centrálne CMS + databázovo riadení KAPZ/APZ + presná reprodukcia povinnej dokumentácie + administrátorský dashboard, reporting a schvaľovanie.**
