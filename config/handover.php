<?php

return [
    /*
    | Fleet types a requester selects when creating a handover.
    | fee_amount (GHS) is charged to the requester when the job is accepted.
    */
    'fleet_types' => [
        'mini_truck' => [
            'label' => 'Mini truck',
            'fee' => 150.00,
        ],
        'medium_truck' => [
            'label' => 'Medium truck',
            'fee' => 250.00,
        ],
        'large_truck' => [
            'label' => 'Large truck',
            'fee' => 400.00,
        ],
        'compactor' => [
            'label' => 'Compactor',
            'fee' => 500.00,
        ],
    ],
];
