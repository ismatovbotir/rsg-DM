<?php

/*
| Document layout per type (keys match config/menu.php items flagged 'doc' => true).
|
| 'header' : step 1 / burger-menu fields => required?  (field definitions are in 'fields')
| 'qty'    : operator may edit quantity on lines
| 'price'  : operator may edit price on lines
| 'production' : ask for the production date in the item entry window (default true; lines are unique per item + date)
|
| Field types: date | text | warehouse (dropdown of warehouses) | pricetype (dropdown of price lists) | counterparty (typed name/STIR or browse list)
*/
return [
    'fields' => [
        'doc_date' => ['label' => 'Sana', 'type' => 'date'],
        'counterparty_id' => ['label' => 'Kontragent', 'type' => 'counterparty'],
        'warehouse_id' => ['label' => 'Ombor', 'type' => 'warehouse'],
        'price_type_id' => ['label' => 'Narx turi', 'type' => 'pricetype'],
        'warehouse_to_id' => ['label' => 'Qabul qiluvchi ombor', 'type' => 'warehouse'],
        'reason' => ['label' => 'Sabab', 'type' => 'text'],
        'comment' => ['label' => 'Izoh', 'type' => 'text'],
    ],

    'types' => [
        'receiving' => [
            'header' => ['doc_date' => true, 'counterparty_id' => true, 'warehouse_id' => true, 'comment' => false],
            'qty' => true, 'price' => true,
        ],
        'pricecheck' => [ // scan shelf items; per line: price correct / queue a new tag. 'check' = verdict lines, no qty
            'header' => ['doc_date' => true, 'price_type_id' => true, 'warehouse_id' => false, 'comment' => false],
            'qty' => false, 'price' => false, 'check' => true, 'sync' => false, // not sent to Adines: it has a print button and a printed status instead
        ],
        'stocktaking' => [
            'header' => ['doc_date' => true, 'warehouse_id' => true, 'comment' => false],
            'qty' => true, 'price' => false,
        ],
        'prices' => [ // price changes: only the new price is entered
            'header' => ['doc_date' => true, 'warehouse_id' => false, 'comment' => false],
            'qty' => false, 'price' => true, 'production' => false,
        ],
        'writeoff' => [
            'header' => ['doc_date' => true, 'warehouse_id' => true, 'reason' => true, 'comment' => false],
            'qty' => true, 'price' => false,
        ],
        'returns' => [
            'header' => ['doc_date' => true, 'counterparty_id' => true, 'warehouse_id' => true, 'comment' => false],
            'qty' => true, 'price' => true,
        ],
        'pickship' => [
            'header' => ['doc_date' => true, 'counterparty_id' => true, 'warehouse_id' => true, 'comment' => false],
            'qty' => true, 'price' => true,
        ],
        'movement' => [
            'header' => ['doc_date' => true, 'warehouse_id' => true, 'warehouse_to_id' => true, 'comment' => false],
            'qty' => true, 'price' => false,
        ],
    ],
];
