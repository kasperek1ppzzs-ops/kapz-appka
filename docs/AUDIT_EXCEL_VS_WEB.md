# Audit: Excel matica vs. webová aplikácia KAPZ / APZ

**Dátum auditu:** 24. 9. 2026 · **Vetva:** `claude-code` · **Commit auditovaného kódu:** `4e27550`
**Zdroje:** `vyplnena matica.xlsm` (referenčné dáta, obdobie 08/2026), `8 matica 2026.xlsm` (šablóna), `ANTIGRAVITY_MATICA_WEB_MASTER_PROMPT.md`

> Táto správa je výsledkom **fázy 1 (analýza)**. Kód aplikácie nebol menený.
> Stav testov pred auditom aj po ňom: **25/25 prechádza** (`php artisan test`, 113 assertions).

---

## 0. Metóda a dôležitá korekcia zadania

### 0.1 Ako bola analýza vykonaná
1. Oba `.xlsm` súbory boli rozbalené ako ZIP (OpenXML) – `xl/workbook.xml`, `xl/worksheets/*.xml`, `xl/calcChain.xml`, definované názvy, externé linky.
2. Python + `openpyxl` načítal **vzorce aj vypočítané hodnoty**. Vzorce boli normalizované na relatívny tvar (R1C1), aby sa 20 000+ vzorcov zredukovalo na unikátne vzory, a pre každý hárok boli spočítané **medzihárkové väzby**.
3. Kód aplikácie bol prečítaný (Services, Controllers, Models, migrácie, Blade/PDF šablóny).
4. Podozrivé miesta boli **overené spustením** – dočasný PHPUnit test (mimo repozitára, po behu zmazaný) a `php artisan tinker`. Výsledky sú uvedené pri jednotlivých nálezoch ako „Overené“.
5. Makrá VBA (`vbaProject.bin`) neboli analyzované (v súlade s master promptom §4).

### 0.2 Korekcia číslovania hárkov
Zadanie auditu predpokladá „dochádzky = hárky 1 až 14“, „hárok 19 = kniha príchodov/odchodov“. **Skutočné poradie hárkov je iné** (rovnaké v šablóne aj vo vyplnenej matici):

| # | Hárok | Stav | Funkcia |
|---|---|---|---|
| 1 | Kalendár | viditeľný | ročný kalendár, **mesačný fond hodín** (stĺpce AF/AG) |
| 2 | Zoznam kontaktov | viditeľný | adresár |
| 3 | **HLASENIE** | viditeľný | **centrálny vstup**: mená, pôsobnosti, os. čísla, stav každého dňa pre KAPZ (stĺpec B) + až 17 APZ (C–S) |
| 4 | Hárok2 | skrytý | pomocný (obsahuje `#REF!` vo vyplnenej matici) |
| 5 | Hárok1 | skrytý | pomocný – podľa master promptu nepotrebný |
| 6 | hlas | skrytý | **číselník 19 stavov dochádzky** |
| 7 | EVIDENCIA_KAPZ | viditeľný | evidencia odpracovanej doby KAPZ |
| 8 | EVIDENCIA_APZ | viditeľný | 17 blokov evidencie APZ |
| 9 | HODNOTENIE_APZ | skrytý | historický, ale **stále zdroj** pre PREHLÁSENIE a Správu o činnosti |
| 10 | Pracovný výkaz | skrytý | historický |
| 11 | PV_KAPZ1 | skrytý | nepotrebný |
| 12 | **KNIHY** | viditeľný | **kniha príchodov a odchodov** (KAPZ + 17 APZ) |
| 13 | PREHLÁSENIE | viditeľný | prehlásenie o činnosti mimo pracovného pomeru |
| 14 | tyzdne | skrytý | tabuľka čísel kalendárnych týždňov |
| 15 | Plán pracovných ciest | viditeľný | 5 týždenných blokov |
| 16 | Cestovný príkaz | viditeľný | mesačný CP + tabuľka vyúčtovania (riadky 85–271) |
| 17 | Vyúčtovanie VD | viditeľný | súpis úsekov a súčet km |
| 18 | GENERATOR | viditeľný | **číselník účelov ciest** + generátor textov správ |
| 19 | Správa z pracovnej cesty | viditeľný | správy (až 32 ciest) |
| 20 | limity a prac. dni | skrytý | **mesačné limity km podľa pôsobnosti** |
| 21 | Správa o pracovnej činnosti | viditeľný | čerpanie limitu km, prehľad ciest |

**Kniha príchodov a odchodov je hárok 12 (KNIHY)**, nie 19. Hárok 19 je „Správa z pracovnej cesty“. Dochádzka nie je v hárkoch 1–14: vstup je v HLASENIE (3) a výstupy v EVIDENCIA_KAPZ/APZ (7, 8).

### 0.3 Rozdiel šablóna vs. vyplnená matica
Vzorce sú prakticky totožné. Rozdiely:
- Vo vyplnenej matici je v `Hárok2!B25` (21 buniek) poškodená referencia `=PREHLÁSENIE!#REF!`.
- PREHLÁSENIE v šablóne pokrýva KAPZ + 17 APZ (riadky až po `A23 = HLASENIE!$S$3`), vo vyplnenej matici iba KAPZ + 7 APZ.
- Oblasť tlače plánu je v šablóne nastavená na 4. týždeň (`A187:L247`), vo vyplnenej na 1. týždeň.

---

## 1. Mapa väzieb v Exceli

### 1.1 Tok dát (medzihárkové väzby, počty odkazov)

```
Zoznam kontaktov ─33─► HLASENIE ◄─2─ Kalendár (fond hodín)
                          │
     ┌──────────1184──────┼──────20128──────┐
     ▼                    │                 ▼
EVIDENCIA_KAPZ            │           EVIDENCIA_APZ
     │ 308                │                 │ 5270
     └────────► KNIHY ◄───┘ 123 ◄───────────┘
                  │ (M5 = mesiac/rok)
                  ▼
       PREHLÁSENIE, Správa o pracovnej činnosti

HLASENIE ─64─► Plán pracovných ciest ◄─2─ tyzdne (č. týždňa)
                  ▲ 1 (číslo CP)   ▲ 5 (GENERATOR)
HLASENIE ─5─► Cestovný príkaz ◄──129──► GENERATOR (86 späť)
                  │ 349                 │ 130
                  ▼                     ▼
            Vyúčtovanie VD      Správa z pracovnej cesty
                  │ I92 (km)            │ 33
                  ▼                     ▼
            Správa o pracovnej činnosti ◄─2─ limity a prac. dni
```

**Dôležité zistenie:** Plán pracovných ciest **nie je zdrojom** pre Cestovný príkaz. Väzba je opačná a slabá – Plán si z CP berie iba číslo (`J4 = 'Cestovný príkaz'!$E$3`). Trasy v CP (riadky 14–46 a 85–268) sa v Exceli **zadávajú ručne** (prípadne cez makro, ktoré nebolo analyzované). Reťaz „Plán ➔ CP ➔ Vyúčtovanie“ teda v Exceli existuje iba medzi CP a Vyúčtovaním VD.

### 1.2 HLASENIE – centrálny vstup (ekvivalent databázy)
| Bunka | Obsah |
|---|---|
| `B1`, `C1…S1` | meno KAPZ, mená APZ (max. 17) |
| `B2`, `C2…S2` | pôsobnosť KAPZ / lokalita APZ |
| `B3`, `C3…S3` | osobné čísla |
| `A4` | 1. deň mesiaca (ručne); `A5:A31 = A4+1`; `A32:A34 = IFERROR(IF(MONTH(A31)<>MONTH(A31+1),"",A31+1),"")` |
| `B4:S34` | **stav dňa** z číselníka `hlas!A2:A20` |
| `B36` | ITMS kód `401405DUQ8` |
| `B37` | pracovná pozícia |
| `B38` | fond: `=SUMIF(Kalendár!AG2:AG149,B40,Kalendár!AF2:AF149)` |
| `B39/B40/B41` | rok / mesiac `"08"` / posledný deň mesiaca `=EOMONTH(A14,0)` |
| `B42` | iniciály (`LB`) – použité v čísle CP |

**Číselník stavov (`hlas`, 19 hodnôt):** Nekomunikuje, V práci, OČR, Dovolenka, 1/2 Dovolenka, PN, Lekár, Lekár - doprovod, Pohreb, 1/2 lekár, Darovanie krvi, 1/2 lekár-doprovod, MD, Sviatok, Neplatené voľno, RD, Absencia, lekár-tehotenstvo, KZ.
Víkendy sa nevypĺňajú (prázdna bunka ⇒ prázdny riadok vo všetkých výstupoch).

### 1.3 EVIDENCIA_KAPZ / EVIDENCIA_APZ – vzorce (deň = riadok 8…38)
| Stĺpec | Vzorec (KAPZ, riadok 8 ↔ HLASENIE!B4) | Význam |
|---|---|---|
| C – Pracovisko | `=IF(HLASENIE!B4="V práci",$G$3,IF(E8=3.75,$G$3))` | pôsobnosť (`G3 = HLASENIE!B2`) len pri práci alebo polovičnom dni; inak prázdne |
| E – Odpracované h | `=IF(B4="V práci",7.5,IF(OR(B4="1/2 Dovolenka",B4="1/2 lekár",B4="1/2 lekár-doprovod"),3.75,""))` | **7,5 h / 3,75 h** |
| F – Neodpracované | vnorený IF, 18 stavov → text (`1/2 Dovolenka`→„Dovolenka“, `1/2 lekár`→„Lekár“) | typ absencie |
| G – Počet hodín | `=IF(OR(<15 stavov absencie vrátane Sviatok, Nekomunikuje>),7.5,IF(E8=3.75,3.75,""))` | **absencia = 7,5 h neodpracovaných** |
| E39 / G39 | `=SUM(E8:E38)`, `=SUM(G8:G38)` | súčty |
| R39 → S17 | `=SUM(E39:G39)` | **kontrola fondu**: E+G sa musí rovnať fondu z Kalendára (podmienené formátovanie „zelený štvorček“) |
| K8…K30 | **ručné** bunky: počet prac. dní, sviatky, dovolenka, PN, OČR, MD/RD, KZ, lekár, doprovod, krv, pohreb, neplatené | pravý panel |
| K32 | `=K8-K10-K12-K14-K16-K18-K22-K24-K26-K28-K30-K20` | skutočne odpracované dni |
| K34 | ten istý rozdiel ako K32 | finančný príspevok – dni |

EVIDENCIA_APZ je 17× ten istý blok (každý 42 riadkov), zdroj `HLASENIE!C…S`; navyše `F41 = E41/7.5` (odpracované dni).
**Poznámky:** Hodnoty 7,5 / 3,75 sú v Exceli pevné – na úväzok sa neprepočítavajú. Pravý panel (K8–K30) sa nepočíta automaticky.

### 1.4 Kalendár – fond pracovného času 2026
`AF` (hodiny) / `AG` (mesiac): 165 / 150 / 165 / 165 / 157,5 / 165 / 172,5 / 157,5 / 165 / 165 / 157,5 / 172,5.
Hodnoty = **počet dní po–pi × 7,5 h** (sviatky pripadajúce na pracovný deň sa **neodpočítavajú** – vykazujú sa v G ako „Sviatok 7,5 h“). Napr. august 2026: 21 dní × 7,5 = 157,5. Sviatky Excel nepozná automaticky (`HolidayTable` = `#REF!`); používateľ ich zadá stavom „Sviatok“.

### 1.5 KNIHY – kniha príchodov a odchodov (hárok 12)
Riadok 9 = 1. deň (KAPZ), bloky APZ po 41 riadkoch:
| Stĺpec | Vzorec | Výsledok |
|---|---|---|
| A | `=HLASENIE!A4` | dátum |
| B/C, D/E | `=IF(N(EVIDENCIA_KAPZ!E8)>=7.5,8,"")`, `…0`, `…16`, `…0` | príchod **8:00**, odchod **16:00** |
| F/G, H/I | `…12`, `…0`, `…12`, `…30` | prerušenie **12:00 – 12:30** |
| J | `=IF(N(E8)>=7.5,"Obed","")` | dôvod prerušenia |
| K, L | prázdne (ručne) | navštívené miesto, schválil |
| M | `=EVIDENCIA_KAPZ!F8` | **poznámka = typ absencie** (Dovolenka, PN, Lekár, Sviatok…) |
| hlavička | `B5` meno, `I5` os. č., `K5 = HLASENIE!B2` lokalita, `K4` ITMS, `M5 = "08/2026"` | |
| päta | `B40 = HLASENIE!B41` (posledný deň mesiaca) | dátum pri podpisoch |

Časy sa vyplnia **iba ak odpracované hodiny ≥ 7,5**. Polovičný deň (3,75 h) ⇒ časy prázdne. Víkend ⇒ celý riadok prázdny.

### 1.6 Plán pracovných ciest (hárok 15)
- 5 blokov po 62 riadkoch (1 blok = 1 kalendárny týždeň). Číslo týždňa: `E3 = tyzdne!A4 = SUMIF(tyzdne!B2:B15, HLASENIE!A4, tyzdne!C2:C15)` (tabuľka 1. dní mesiacov → ISO týždeň; 08/2026 → 31), ďalšie bloky `+1`.
- **Každý úsek = dvojica riadkov**: `Odchod` (miesto D, čas E) / `Príchod` (miesto D, čas E). Stĺpce: F dopravný prostriedok, G km, H účel (číselník GENERATOR), J stručný opis, K ubytovanie, L spolucestujúci.
- **4 časové body** jednej cesty = 2 dvojice riadkov (tam: odchod z bázy/príchod do cieľa; späť: odchod z cieľa/príchod na bázu). Príklad 31. 7.: Snina 8:00 → Kučín 8:45; Kučín 14:05 → Snina 15:00; km 40,5 pri každom úseku.
- **Viac lokalít/ciest v 1 deň** = ďalšie dvojice s rovnakým dátumom (napr. 30. 7. dva úseky).
- **Absencie sa zapisujú do plánu** – v stĺpci J (napr. 27.–29. 7. „Dovolenka“).
- Súčet km za týždeň `O43 = SUM(G10:G45)`.
- Schvaľovateľ (ZFK) `A61` = vnorený IF podľa pôsobnosti (34 pôsobností → Koky / Nazarejová / Pišta).
- Legenda dopravy (M1–M8): AUS, AUV, AUSS, AUVS, O, R, A, P.

### 1.7 Cestovný príkaz (hárok 16) a Vyúčtovanie VD (hárok 17)
- **1 CP na mesiac a KAPZ**, číslo `E3 = mesiac & "/" & rok & "/" & iniciály & "/ZK"` → `08/2026/LB/ZK`.
- Riadky 14–46: súhrn ciest (začiatok miesto/dátum/hodina, **miesto konania – viac obcí oddelených čiarkou**, účel, koniec) – ručne.
- Riadky 85–268: úseky Odchod/Príchod (R dátum, T miesto, U čas, V doprava, W km) a výpočty:
  | Bunka | Vzorec | Význam |
  |---|---|---|
  | `Y85` | `=$AI$83/100*W85*AI85` | **PHM** = spotreba (l/100 km, AI83) × km × cena PH za týždeň (AI) |
  | `Z85` | ručne | amortizácia |
  | `AA85`, `AB85`, `AC85` | ručne | **stravné, nocľažné, nutné vedľajšie výdavky** (Excel ich nepočíta) |
  | `AD85` | `=Y85+Z85+AA85+AB85+AC85` | spolu |
  | `BF85…BH85` | `=IF(V85="AUV",1,0)`, `=IF(BF85=1,W85*0.183,0)`, `=IF(BF85=1,W85,0)` | pomocné: km vlastným autom a **sadzba 0,183 €/km** |
  | `AD269` | `=SUM(AD85:AD268)` | celkom |
  | `AD270` | ručne | preddavok |
  | `AD271` | `=AD269-AD270` | **doplatok / preplatok** → `F62/J63/F64` v hornej časti CP |
  | `H466` | `=SUM(BH85:BH268)` | km vlastným autom spolu |
- Vyúčtovanie VD: `A/B/F/I` riadky 5–91 = dátum / odkiaľ / kam / km z CP; `I92 = SUM(I5:I91)` = **najazdené km** (187,3 km vo vzorke).

### 1.8 GENERATOR (hárok 18) a správy
- Číselník **19 oficiálnych účelov KAPZ** (`P14:Q32`) + ku každému text „Závery/Odporúčania“ (`R`, `S`). Druhý číselník účelov pre Experta pre terén (`P41:S55`).
- Pre každú cestu z CP (riadky 14–46) generuje blok správy: deň, miesto, účel a záver vyhľadaný vnoreným IF podľa účelu (`E16 = IF(E13=Q14,S14,…)`).
- Správa z pracovnej cesty (hárok 19) preberá dáta z CP a GENERATOR (32 blokov).
- Správa o pracovnej činnosti (hárok 21):
  - `H11 = SUMIF('limity a prac. dni'!B3:B45, pôsobnosť, C3:C46)` – **mesačný limit km**,
  - `H12 = 'Vyúčtovanie VD'!I92` – **najazdené km (skutočnosť z CP, nie plán)**,
  - `H13 = H11-H12` – zostatok.

### 1.9 limity a prac. dni (hárok 20)
33 pôsobností s limitom km (napr. Snina 650, Levoča **400**, Košice 380, Košice-okolie 530, Poprad 400, Poprad - okolie 745, „Velký Krtíš“ 1035).

### 1.10 Chyby a slabé miesta v samotnom Exceli (pre informáciu)
1. `Plán!G16` obsahuje čas 12:00 namiesto km – `SUM` ho započíta ako 0,5 km (týždenný súčet 82 namiesto 81,5).
2. Limit sa hľadá cez `SUMIF` podľa textu: „Velký Krtíš“ (limity) vs. „Veľký Krtíš“ (mapa schvaľovateľov) ⇒ KAPZ s pôsobnosťou „Veľký Krtíš“ dostane limit **0**. „Žilina a okolie“ má schvaľovateľa, ale nemá limit.
3. `Hárok2!B25` = `#REF!` vo vyplnenej matici.
4. Pravý panel evidencie (K8–K30) sa nepočíta – je náchylný na ručné chyby.

---

## 2. Zhodné moduly (OK)

| Oblasť | Excel | Web | Stav |
|---|---|---|---|
| Norma dňa | 7,5 h | `AttendanceController` predvyplnenie 7,5 h | ✅ |
| Pracovisko pri práci / absencii | `C8`: pôsobnosť len pri „V práci“ | `saveKapzAttendance` ukladá `workplace` iba pri `work`, JS pole vyprázdni a zamkne | ✅ (okrem polovičných dní, pozri 3.1) |
| Skutočne odpracované dni a Fin. príspevok | `K32`, `K34` | `actual_work_days`, `financial_days` = počet dní `work` | ✅ výsledok zhodný pri úplných dátach |
| Názvy a poradie riadkov pravého panela v PDF | H8–H35 | `pdf/evidencia_kapz.blade.php` `$summaryRows` 1–15 | ✅ texty a poradie sedia |
| Stĺpce evidencie | Deň, Pracovisko, Odpracované, Počet hodín, Neodpracované, Počet hodín, Nevyplňovať, dni/€ | rovnaké v PDF | ✅ |
| Kniha – štandardný deň | 8:00–16:00, prerušenie 12:00–12:30 „Obed“ | `ArrivalDepartureBookService::syncBookItems` pre `work` | ✅ |
| Kniha – hlavička/päta | ITMS, meno, os. č., mesiac, dátum = posledný deň mesiaca | `kniha_prichodov_odchodov.blade.php` | ✅ (lokalita a schvaľovateľ – pozri 3.3) |
| Kalendárny týždeň plánu | `tyzdne` → ISO týždeň 1. dňa mesiaca | `TravelPlan::getCalendarWeek` (ISO týždeň pondelka 1. týždňa) | ✅ 08/2026 → 31 |
| 4 časové body cesty | 2 dvojice Odchod/Príchod | migrácia `2026_09_07_215426_add_four_times…` + `addItem/updateItem` | ✅ pre jednoduchú cestu tam a späť |
| Viac ciest v 1 deň | viac dvojíc s rovnakým dátumom | `travel_plan_items` nemá unikátny kľúč na dátum | ✅ |
| Okružná trasa | CP `E14` „Ubľa, Stakčín“ + úseky 85–90 | `DistanceCalculationService::getMultiStopDistance` A→B→C→A cez vzdialenosti.sk, fallback OSRM | ✅ výpočet km |
| Limity km – väčšina pôsobností | `limity a prac. dni` | `TravelWorkflowService::REGION_KM_LIMITS` | ✅ 32 z 33 hodnôt v tabuľke zhodných (výnimka Levoča); chybné je však vyhľadávanie – pozri P2 |
| Workflow plánu | – (Excel nemá) | DRAFT → SUBMITTED → APPROVED/RETURNED, audit log, verzia pri vrátení | ✅ podľa master promptu §13 (bez stavu ZAMIETNUTÝ) |
| ZFK blok v pláne | `A50–A62`, zákon 357/2015 Z. z. | `plan_pracovnych_ciest.blade.php` | ✅ obsahovo |
| Historizované priradenia APZ | – | `apz_assignments` + `assignedApzsForDate()` | ✅ podľa master promptu §8 |

---

## 3. Nezrovnalosti a medzery

Závažnosť: 🔴 vysoká (chybný výstup alebo oficiálny dokument) · 🟠 stredná · 🟡 nízka

### 3.1 Dochádzka (EVIDENCIA_KAPZ / EVIDENCIA_APZ)

| # | Závažnosť | Nález | Excel | Web (súbor) |
|---|---|---|---|---|
| D1 | 🔴 | **Hodiny absencie sú 0 namiesto 7,5.** Neodpracované hodiny (stĺpec G) a kontrola fondu E+G nesedia. | `G8` = 7,5 pri každej absencii vrátane Sviatku | JS `handleKapzStatusChange` nastaví `0.0`; `save*Attendance` default `0.00` (`AttendanceController.php:45, 165`); PDF tlačí `hours_worked` absencie (`evidencia_kapz.blade.php`) ⇒ „0,0“ |
| D2 | 🔴 | **Chýbajú polovičné dni** (1/2 Dovolenka, 1/2 lekár, 1/2 lekár-doprovod: 3,75 + 3,75 h, pracovisko vyplnené). | `E8`, `G8`, `C8` | nie sú v `<select>` (`attendance/kapz.blade.php:137-150`) ani v kalkulačke |
| D3 | 🔴 | **Chýbajú stavy** MD, RD, Absencia, lekár-tehotenstvo, KZ (a „Neplatené voľno“ má iný názov). Naopak web má „Náhradné voľno“, ktoré v matici nie je. | `hlas!A2:A20` (19 stavov) | 14 stavov; `md_rd` sa počíta v `AttendanceCalculatorService`, ale nedá sa zvoliť |
| D4 | 🟠 | **Rôzne kódy pre ten istý stav:** KAPZ `nv`, APZ `substitute_leave`. APZ kalkulačka ho nepočíta vôbec. | – | `attendance/kapz.blade.php` vs `attendance/apz.blade.php`; `calculateApzMonthlySummary` |
| D5 | 🔴 | **„Počet pracovných dní“ je zle.** Web = súčet dní so zadaným stavom (bez MD/RD, neplateného, „Nekomunikuje“), pri prázdnom mesiaci natvrdo **23**. Overené: 5 zadaných dní ⇒ 5; prázdny mesiac ⇒ 23 (august 2026 má 21). | fond = dni po–pi × 7,5 (Kalendár AF) | `AttendanceCalculatorService.php:44, 53, 94, 103` |
| D6 | 🟠 | **APZ súhrn neráta** KZ, MD/RD, krv, pohreb, neplatené – PDF má tieto riadky natvrdo prázdne. | 17 blokov rovnakých ako KAPZ | `calculateApzMonthlySummary`; `evidencia_apz.blade.php:84-90` |
| D7 | 🟠 | **Do oficiálneho PDF sa tlačí interný kód** (`no_communication`, `nv`, `substitute_leave`) cez `@default {{ $status }}`. | F = slovenský text | `evidencia_kapz.blade.php` (switch) |
| D8 | 🟠 | Chýba **kontrola fondu** (Excel S17/R39 „zelený štvorček“). | `R39 = E39+G39` vs `HLASENIE!B38` | žiadna validácia |
| D9 | 🟡 | Sviatky: Excel ich nepozná automaticky, web má natvrdo zoznam pevných dátumov bez pohyblivých sviatkov (Veľký piatok, Veľkonočný pondelok) a bez ročnej platnosti; zoznam je duplikovaný v 2 súboroch. Aktuálnosť zoznamu voči zákonu č. 241/1993 Z. z. treba overiť. | stav „Sviatok“ ručne | `ArrivalDepartureBookService.php:105`, `CalendarController.php:36-50` |
| D10 | 🟡 | Kód číta neexistujúce stĺpce `employment_ratio`, `community_scope`, `community_name`, `base_municipality` (nie sú v migráciách) ⇒ vždy fallback. Excel úväzok nepozná (vždy 7,5). | – | `AttendanceController`, `ArrivalDepartureBookService` |
| D11 | 🟡 | PDF „Pracovisko“ môže vytlačiť `activity_description` namiesto pôsobnosti, ak `workplace` chýba. | `C8` = vždy pôsobnosť | `evidencia_kapz.blade.php` (stĺpec Pracovisko) |

### 3.2 KNIHY (kniha príchodov a odchodov)

| # | Závažnosť | Nález | Excel | Web |
|---|---|---|---|---|
| K1 | 🔴 | **Porovnávanie stavov nefunguje.** Kód porovnáva `strtoupper($status)` s `'DOVOLENKA'`, `'LEKAR'`, `'SVIATOK'`, `'WORK'`, ale v DB sú kódy `holiday`, `doctor`, `public_holiday`, `work`. **Overené:** Dovolenka, Lekár aj Sviatok (0 h) ⇒ prázdny riadok **bez poznámky**. | `M9 = EVIDENCIA!F8` („Dovolenka“, „Lekár“, „Sviatok“) | `ArrivalDepartureBookService.php:134-160` |
| K2 | 🔴 | **Falošná prítomnosť:** absencia s nenulovými hodinami (napr. Dovolenka 7,5 h) dostane 08:00–16:00 „Obed“ (vetva `$hoursWorked > 0`). **Overené.** Po oprave D1 (7,5 h pri absenciách) by takto dopadla **každá** absencia. | časy iba ak **odpracované** ≥ 7,5 | `ArrivalDepartureBookService.php:148` |
| K3 | 🟠 | Pracovný deň **bez záznamu dochádzky** sa predvyplní 8:00–16:00. | prázdny stav ⇒ prázdny riadok | `ArrivalDepartureBookService.php:166-178` |
| K4 | 🟠 | Absencia sa píše do „Dôvod odchodu počas pracovného času“ (`break_reason`). | absencia iba v Poznámke (M), J = len „Obed“ | `break_reason = 'Dovolenka'` atď. |
| K5 | 🟡 | Víkend má poznámku „Víkend“. | prázdny riadok | `note = 'Víkend'` |
| K6 | 🟠 | Lokalita KAPZ = `base_municipality` (stĺpec neexistuje) ⇒ vždy **„Banská Bystrica“**; APZ ⇒ „Lokalita APZ“. | `K5 = HLASENIE!B2` (pôsobnosť, napr. Snina) | `ArrivalDepartureBookService.php:42, 48` |
| K7 | 🟡 | Schvaľovateľ natvrdo „Mgr. Ľudmila Grešková“, ITMS natvrdo v kóde. | L „Schválil“ ručne; ITMS z `HLASENIE!B36` | `ArrivalDepartureBookService.php:43, 63` |
| K8 | 🟡 | Polovičný deň: Excel nechá časy prázdne (3,75 < 7,5); web polovičné dni nepozná (D2). | | |

### 3.3 Plán pracovných ciest

| # | Závažnosť | Nález | Excel | Web |
|---|---|---|---|---|
| P1 | 🔴 | **Číselník účelov nesedí.** Web má 7 dlhých textov (sú to texty „Závery“ zo stĺpca R), Excel 19 krátkych účelov (Q). Chýba napr. najpoužívanejší „Kontrolná, podporná a hodnotiaca činnosť APZ“ aj „Odborné riadenie koordinačných stretnutí APZ“. | `GENERATOR!Q14:Q32` + `R` | `TravelWorkflowService::OFFICIAL_PURPOSES` |
| P2 | 🔴 | **Chybné limity km.** Overené cez tinker: *Košice → 530* (Excel 380), *Poprad - okolie → 400* (Excel 745) – podreťazcové porovnávanie `mb_stripos` vráti prvú čiastočnú zhodu; *Levoča 525* (Excel 400); neznáma pôsobnosť ⇒ 500 (Excel 0); pribudli „Banská Bystrica“ 1250. | `limity a prac. dni` | `TravelWorkflowService.php:21-56, 63-76` |
| P3 | 🟠 | **Dopravné prostriedky nesedia.** Web: AUV, AAuto, VHD (default migrácie `AAuto`). Excel: AUS, AUV, AUSS, AUVS, O, R, A, P. Od kódu závisí výpočet amortizácie (`V="AUV"`). | legenda CP R276–U279 | `TravelPlanController.php:110-114`, migrácia `…000005` |
| P4 | 🟠 | **Okružná trasa má iba 4 časy.** Pri trase Snina → Ubľa → Stakčín → Snina Excel eviduje 6 časov (3 úseky), web iba 4 a km len súhrnne. Medzičasy a km po úsekoch (potrebné pre CP a Vyúčtovanie VD) sa strácajú. | CP riadky 85–90 | `travel_plan_items` (1 riadok = 1 cesta) |
| P5 | 🟠 | **PDF si vymýšľa časy:** ak čas chýba, vytlačí 09:00 / 14:00 / 15:00. | prázdna bunka | `plan_pracovnych_ciest.blade.php:229-230` |
| P6 | 🟠 | **Chýbajú stĺpce** Ubytovanie, Spolucestujúca osoba; „Účel“ a „Stručný opis“ sú zlúčené do jedného. | stĺpce H, J, K, L | `travel_plan_items`, PDF |
| P7 | 🟠 | **Absencie v pláne** (Dovolenka a pod. v stĺpci J) sa nepreberajú z dochádzky. | `J10 = 'Dovolenka'` | – |
| P8 | 🟠 | Týždeň (1–5) zadáva používateľ ručne; nekontroluje sa, či `trip_date` patrí do týždňa ani do mesiaca plánu. | týždeň = pozícia bloku podľa dátumu | `addItem` validácia |
| P9 | 🟠 | Schvaľovateľ ZFK: Excel podľa pôsobnosti (mapa 34 pôsobností → 3 osoby); web `reviewedBy` / `region_expert` / natvrdo „Koky Richard“. | `A61` | `plan_pracovnych_ciest.blade.php:288, 306` |
| P10 | 🟡 | Číslo CP: web `MM/YYYY/{id}/ZK`, Excel `MM/YYYY/{iniciály}/ZK` (`08/2026/LB/ZK`). V DB chýbajú iniciály. | `CP!E3` | `TravelPlan::getOrderNumberAttribute` |
| P11 | 🟡 | Pracovná pozícia v PDF natvrdo „Koordinátor asistentov podpory zdravia“. | `HLASENIE!B37` | `plan_pracovnych_ciest.blade.php:184` |
| P12 | 🟡 | Stav ZAMIETNUTÝ (REJECTED) je v komentári migrácie, ale workflow ho nemá. | master prompt §13 | `TravelWorkflowService` |

### 3.4 Cestovný príkaz, vyúčtovanie, správy

| # | Závažnosť | Nález | Excel | Web |
|---|---|---|---|---|
| C1 | 🔴 | **CP a vyúčtovanie sa nedajú vytvoriť.** Záznamy `travel_orders`, `travel_expenses`, `travel_reports` vznikajú iba v `DatabaseSeeder`. Neexistuje prenos Plán → CP. | CP hárok 16 | `TravelOrderController` má iba index/show/pdf |
| C2 | 🔴 | **Iná kardinalita:** Excel = **1 CP za mesiac** (všetky cesty a úseky), web = CP naviazaný 1:1 na položku plánu. | `CP!E3`, riadky 14–46, 85–268 | `travel_orders.travel_plan_item_id` |
| C3 | 🔴 | **Chýbajú výpočty vyúčtovania:** PHM (`spotreba/100 × km × cena PH`), amortizácia (0,183 €/km pre AUV), súčty stĺpcov, preddavok, doplatok/preplatok. Web má statické polia, sadzbu `0.252` (v matici nie je) a nič ich nepočíta. | `Y85`, `BG85`, `AD269–AD271` | `travel_expenses` (migrácia `…000005`) |
| C4 | 🟠 | Stravné, nocľažné a vedľajšie výdavky sú aj v Exceli ručné – web by ich mal aspoň evidovať po úsekoch (dnes iba 1 číslo `diem_compensation`). Automatický výpočet stravného odporúčam až po potvrdení pravidiel (zákon č. 283/2002 Z. z. a aktuálne opatrenie MPSVR – sadzby treba overiť). | `AA85` ručne | – |
| C5 | 🔴 | `GET /travel/orders/{order}` padne – view `travel.order_detail` neexistuje. | – | `TravelOrderController.php:20` |
| C6 | 🟠 | PDF CP nekopíruje predlohu (chýba bydlisko, ŠPZ, pracovný čas 8:00–16:00, tabuľka úsekov, účtovací predpis 512100/333100, legenda, poznámka k AUV). | `CP!A1:N79`, `R80:AE281` | `pdf/cestovny_prikaz.blade.php` |
| C7 | 🟠 | **Správa z pracovnej cesty + GENERATOR** (automatický text „Závery/Odporúčania“ podľa účelu) nie sú implementované. | hárky 18, 19 | `travel_reports` bez generovania |
| C8 | 🟠 | **Čerpanie limitu:** Excel porovnáva limit so **skutočne najazdenými km** z CP (`Vyúčtovanie VD!I92`), web s **plánovanými** km. | `Správa o pracovnej činnosti!H11–H13` | `TravelPlanController::index` (`total_km`) |
| C9 | 🟠 | Správa o pracovnej činnosti v PDF vypisuje `json_encode` metrík; chýba čerpanie limitu, týždenná tabuľka kontrol APZ, zoznam ciest. | hárok 21 | `pdf/sprava_o_cinnosti.blade.php` |
| C10 | 🟡 | PREHLÁSENIE: Excel = tabuľka KAPZ + všetci APZ (os. č., meno, zárobková činnosť áno/nie, EŠIF, typ úväzku, dátum = posledný deň mesiaca). Webový `pdf/prehlasenie.blade.php` je voľný text; `prehlasenie_cinnosti.blade.php` má položky – odporúčam porovnať s predlohou (§36 master promptu). | hárok 13 | `StatementController` |

### 3.5 Bezpečnosť (zistené počas auditu – overené testom)
Nesúvisí priamo s Excelom, ale blokuje produkčné nasadenie (master prompt §29):

| # | Závažnosť | Nález | Overenie |
|---|---|---|---|
| S1 | 🔴 | KAPZ **stiahne PDF evidencie iného KAPZ** zmenou `kapz_id` (IDOR). Rovnako `save*`, `quickFill*`, APZ PDF – žiadna kontrola vlastníctva. | `GET /attendance/kapz/pdf?kapz_id=<iný>` ⇒ **200** |
| S2 | 🔴 | KAPZ si **sám schváli** svoj plán (`/travel/approve` bez kontroly roly). To isté `/travel/return`. | stav po POST ⇒ **APPROVED** |
| S3 | 🔴 | Admin routy (`/admin/*`) nemajú middleware – bežný KAPZ vidí audit log a môže meniť priradenia. | `GET /admin/audit-logs` ⇒ **200** |
| S4 | 🟠 | `addItem`/`updateItem`/`deleteItem`/`submit`/`downloadPdf` plánu nekontrolujú vlastníka plánu. | analýza kódu |
| S5 | 🟠 | `DistanceCalculationService`: `CURLOPT_SSL_VERIFYPEER => false`; natvrdo API kľúč mapy.cz prevzatý z webu vzdialenosti.sk (krehké, právne neisté). | analýza kódu |

### 3.6 Súlad so zadávacou špecifikáciou
- Master prompt §2 a §39 vyžaduje **MySQL**, projekt používa SQLite (na vývoj OK, pre produkciu rozpor).
- Chýbajú súbory `EXCEL_MAPPING.md`, `DATABASE_SCHEMA.md`, `WORKFLOWS.md`, `PDF_TEMPLATES.md`, `CHANGELOG.md`, `AGENTS.md` (§37). Táto správa môže slúžiť ako základ `EXCEL_MAPPING.md`.
- Chýbajú porovnávacie testy „rovnaký vstup v Exceli vs. vo webe“ (§35).

---

## 4. Kontrola 4 požiadaviek zo zadania

| Požiadavka | Výsledok |
|---|---|
| **4 časové body pri každej ceste** | ⚠️ **Čiastočne.** DB a formulár majú 4 časy (`departure_time`, `arrival_at_dest_time`, `departure_from_dest_time`, `arrival_time`). Chyby: body 2 a 3 sú nepovinné a PDF ich pri absencii **nahradí vymyslenými časmi** (P5); body 1 a 4 majú default 08:00/16:00 v kontroléri aj migrácii; pri okružnej trase chýbajú medzičasy (P4); nevaliduje sa poradie časov (1 < 2 < 3 < 4). |
| **Viac lokalít v 1 deň / viac ciest v ten istý deň** | ⚠️ **Čiastočne.** Viac ciest v jeden deň ✅. Okružná trasa cez čiarku ✅ a km sa počítajú A→B→C→A ✅. Ale km sa neukladajú po úsekoch a medzičasy chýbajú, takže z plánu sa nedá zostaviť tabuľka úsekov CP (riadky 85+) ani Vyúčtovanie VD. |
| **Pracovisko: pri práci obec/región, pri absencii prázdne** | ✅ **Áno** pre celé dni (server aj JS). ❌ Polovičné dni chýbajú – Excel pri nich pracovisko vyplní (D2). 🟡 PDF môže vytlačiť popis činnosti namiesto pracoviska (D11). |
| **Automatická kniha 08:00–16:00, obed 12:00–12:30** | ⚠️ **Pre pracovné dni áno, pre absencie nie.** Absencie nemajú poznámku, absencia s hodinami dostane falošnú prítomnosť, deň bez záznamu sa predvyplní, lokalita je natvrdo „Banská Bystrica“ (K1–K6). |

---

## 5. Odporúčania (v poradí priority)

Každý bod je samostatná, testovateľná zmena. Nič z toho nebolo v tejto fáze implementované.

### Priorita 1 – bezpečnosť a chybné výstupy
1. **Autorizácia (S1–S4).** Pridať `Policy` pre `KapzProfile`, `TravelPlan`, `ArrivalDepartureBook`, `AttendanceKapz/Apz` a admin middleware (napr. `EnsureUserIsAdmin`) pre `/admin/*`, `travel.approve`, `travel.return`. *Súbory:* `routes/web.php`, `bootstrap/app.php`, nové `app/Policies/*`, `AttendanceController`, `TravelPlanController`, `BookReportController`. *Prečo:* master prompt §9, §29; dnes je únik dát a samoschválenie reálne.
2. **Jednotný číselník stavov dochádzky (D3, D4, D7).** Vytvoriť tabuľku alebo PHP enum `AttendanceStatus` s 19 stavmi z `hlas`, pre každý: slovenský text do PDF, odpracované h (7,5 / 3,75 / 0), neodpracované h (7,5 / 3,75 / 0), riadok pravého panela, text poznámky do knihy, či sa vypĺňa pracovisko. *Súbory:* nový `app/Enums/AttendanceStatus.php` (alebo tabuľka `attendance_types` podľa §30), `attendance/kapz.blade.php`, `attendance/apz.blade.php`, `AttendanceCalculatorService`, oba PDF. *Prečo:* jediný zdroj pravdy odstráni D1–D7 aj K1 naraz. Zjednotiť `nv`/`substitute_leave` dátovou migráciou.
3. **Hodiny absencií a polovičné dni (D1, D2).** Neodpracované hodiny počítať z enumu (nie z ručného poľa), pridať 3 polovičné stavy. *Súbory:* `AttendanceCalculatorService`, `AttendanceController::save*`, JS v `attendance/*.blade.php`, `pdf/evidencia_*.blade.php`.
4. **Oprava knihy (K1–K6).** Riadiť sa pravidlom Excelu: časy iba ak **odpracované ≥ 7,5 h**; inak poznámka = text absencie z enumu; víkend a deň bez záznamu prázdny; lokalita = `kapz.scope` / `apz.scope`. *Súbor:* `ArrivalDepartureBookService::syncBookItems`. *Poradie:* nasadiť **spolu s bodom 3**, inak sa zhorší K2.
5. **Fond pracovného času (D5, D8).** Služba `WorkingTimeFundService`: dni po–pi × 7,5 za mesiac (zhoda s `Kalendár!AF`), „Počet pracovných dní“ = fond / 7,5, kontrola `odpracované + neodpracované = fond` ako upozornenie pred uzavretím obdobia. Odstrániť fallback 23. *Súbory:* nový service, `AttendanceCalculatorService`, dashboard.
6. **Limity km (P2).** Presunúť limity do DB tabuľky `monthly_limits` (master prompt §7, §30) naviazanej na pôsobnosť cez ID, nie cez podreťazec; opraviť Levoča = 400; rozhodnúť o „Banská Bystrica“ a „Žilina a okolie“. *Súbory:* nová migrácia a seeder, `TravelWorkflowService::getLimitForScope`.
7. **Číselník účelov (P1).** Tabuľka `travel_purposes` (kód, krátky účel = Q, text záverov = R, rola KAPZ/Expert) naplnená z `GENERATOR`. V pláne ukladať FK na účel. *Súbory:* migrácia, seeder, `TravelWorkflowService`, `travel/index.blade.php`.
8. **PDF plánu nesmie vymýšľať časy (P5).** Namiesto `?: '09:00'` tlačiť prázdnu bunku. *Súbor:* `plan_pracovnych_ciest.blade.php:229-230`. (Malá, izolovaná zmena.)
9. **Chýbajúci view (C5).** Doplniť `resources/views/travel/order_detail.blade.php` alebo routu dočasne odstrániť.

### Priorita 2 – dátový model ciest podľa Excelu
10. **Úseky ciest.** Nová tabuľka `travel_segments` (plan_item_id, poradie, odkiaľ, čas odchodu, kam, čas príchodu, km, dopravný prostriedok). Cesta = zoznam úsekov; 4 časy jednoduchej cesty = 2 úseky, okruh = N úsekov. Stĺpce 4 časov ponechať kvôli spätnej kompatibilite. *Prečo:* presne zodpovedá dvojiciam Odchod/Príchod v Pláne aj v CP riadkoch 85+; `DistanceCalculationService` už vracia `segments`. Pridať validáciu poradia časov a príslušnosti dátumu k týždňu/mesiacu (P8).
11. **Dopravné prostriedky (P3)** – číselník AUS/AUV/AUSS/AUVS/O/R/A/P; dátová migrácia `AAuto`→`AUS`, `VHD`→ podľa rozhodnutia používateľa (A alebo O/R).
12. **Stĺpce plánu (P6, P7)** – ubytovanie, spolucestujúci, oddeliť účel a opis; absencie z dochádzky zobraziť v pláne ako read-only riadky.
13. **Mesačný CP (C1–C3).** `travel_orders` 1 : mesiac : KAPZ, generovaný zo schváleného plánu (úseky → riadky CP). `TravelExpenseCalculator`: PHM = spotreba × km × cena / 100, amortizácia = km × sadzba (AUV), súčty, preddavok, doplatok/preplatok. Sadzby (0,183 €/km, spotreba, ceny PH) ako konfigurovateľné hodnoty s platnosťou od–do, **nie** natvrdo. Do profilu KAPZ doplniť iniciály, bydlisko, ŠPZ (P10).
14. **Správa z pracovnej cesty (C7)** – generovať z CP + textu záverov z `travel_purposes`.
15. **Čerpanie limitu (C8)** – rozlišovať plánované km (plán) a skutočné km (CP), správa o činnosti používa skutočné.
16. **Schvaľovateľ podľa pôsobnosti (P9)** – tabuľka pôsobností s FK na Experta pre terén namiesto natvrdo mien.

### Priorita 3 – vernosť dokumentov a procesy
17. PDF CP, Správa o pracovnej činnosti a PREHLÁSENIE prepracovať podľa predlôh (C6, C9, C10) a urobiť obsahové porovnanie podľa §36.
18. Centrálna tabuľka `holidays` s rokom platnosti a pohyblivými sviatkami (D9) – použiť v kalendári, knihe aj fonde; zoznam overiť voči platnému zneniu zákona.
19. Porovnávacie testy Excel ↔ web (§35): referenčné vstupy z `vyplnena matica.xlsm` (HLASENIE 08/2026) → očakávané hodnoty `E39=67,5`, `G39=7,5`, kniha 3. 8. = 8:00/16:00/Obed, 13. 8. = poznámka „Dovolenka“, limit Snina 650, najazdené 187,3 km.
20. Dokumentácia podľa §37 (`EXCEL_MAPPING.md` môže vychádzať z kapitoly 1 tejto správy), prechod na MySQL pred produkciou.

---

## 6. Obmedzenia auditu
- Makrá VBA neboli analyzované; ak CP riadky 14–46 plní makro z Plánu, väzba Plán → CP môže existovať mimo vzorcov.
- Volania vzdialenosti.sk / OSRM neboli spúšťané (sieť); hodnotená je iba logika.
- Vizuálne (pixelové) porovnanie PDF s Excelom nebolo robené – porovnané sú polia, poradie a výpočty.
- Právne sadzby (stravné, náhrada za km, sviatky) sú uvedené iba tak, ako sú v matici; ich aktuálnosť treba overiť v platných predpisoch.
