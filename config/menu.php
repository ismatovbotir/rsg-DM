<?php

/*
| PDA main menu (Uzbek, Latin script), modelled on Cleverence Retail 15.
| Order and hardware keys agreed with the UX designer, the warehouse expert and the project manager:
| assigned tasks first, then operations by real daily frequency, reference tools last.
|
| 'icon'   : id of a symbol in resources/views/partials/icons.blade.php (without the "i-" prefix).
| 'doc'    : true = the operation creates documents (draft -> completed -> synced to Adines).
| 'route'  : named route to open instead of the placeholder screen.
| 'hotkey' : hardware key 0-9 that opens the row. Bound to the operation, not the position,
|            so warehouse muscle memory survives rows appearing and disappearing.
*/
return [
    'app_name' => 'Retail 15',

    'items' => [
        ['key' => 'tasks',       'title' => 'Vazifalarim',             'icon' => 'tasks',       'route' => 'tasks.index', 'hotkey' => 0],
        ['key' => 'receiving',   'title' => 'Qabul qilish',            'icon' => 'receiving',   'doc' => true, 'hotkey' => 1],
        ['key' => 'pricecheck',  'title' => 'Narxni tekshirish',       'icon' => 'pricecheck',  'doc' => true, 'hotkey' => 2],
        ['key' => 'stocktaking', 'title' => 'Inventarizatsiya',        'icon' => 'stocktaking', 'doc' => true, 'hotkey' => 3],
        ['key' => 'movement',    'title' => 'Ko‘chirish',              'icon' => 'movement',    'doc' => true, 'hotkey' => 4],
        ['key' => 'writeoff',    'title' => 'Hisobdan chiqarish',      'icon' => 'writeoff',    'doc' => true, 'hotkey' => 5],
        ['key' => 'returns',     'title' => 'Tovarni qaytarish',       'icon' => 'returns',     'doc' => true, 'hotkey' => 6],
        ['key' => 'prices',      'title' => 'Narxlarni o‘zgartirish',  'icon' => 'prices',      'doc' => true, 'hotkey' => 7],
        ['key' => 'pickship',    'title' => 'Terish va jo‘natish',     'icon' => 'pickship',    'doc' => true, 'hotkey' => 8],
        ['key' => 'barcodes',    'title' => 'Shtrix-kodlarni yig‘ish', 'icon' => 'barcodes',    'hotkey' => 9],
        ['key' => 'directories', 'title' => 'Ma’lumotnomalar',         'icon' => 'directories', 'route' => 'directory'],
    ],

    // Side (burger) menu: system-level actions. 'logout' => true renders a sign-out button.
    'side' => [
        ['key' => 'settings', 'title' => 'Sozlamalar',                   'icon' => 'settings', 'route' => 'settings'],
        ['key' => 'user',     'title' => 'Foydalanuvchini almashtirish', 'icon' => 'user', 'logout' => true],
        ['key' => 'exit',     'title' => 'Chiqish',                      'icon' => 'exit', 'logout' => true],
    ],
];
