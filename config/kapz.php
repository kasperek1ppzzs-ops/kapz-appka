<?php

return [
    // ITMS kód projektu (Excel HLASENIE!B36) – zobrazuje sa v KNIHY a v pláne ciest.
    'itms_code' => env('KAPZ_ITMS_CODE', '401405DUQ8'),

    // Zamestnávateľ a útvar (Cestovný príkaz B2, K3)
    'employer' => env('KAPZ_EMPLOYER', 'Zdravé regióny, Limbová 2, 83752 Bratislava'),
    'department' => env('KAPZ_DEPARTMENT', 'Zdravé regióny'),

    // ZFK – bod 1 overenia súladu (Plán pracovných ciest A57)
    'zfk_verifier' => env('KAPZ_ZFK_VERIFIER', 'Mgr. Lenka Eremiaš'),

    // Pracovná pozícia KAPZ (HLASENIE!B37)
    'kapz_position' => 'Koordinátor asistentov podpory zdravia',
];
