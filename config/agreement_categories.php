<?php

/**
 * Default agreement category definitions (seeded per company on first access).
 * Add new groups/types here for future agreement modules without code changes in controllers.
 */
return [
    'groups' => [
        'rider_agreements' => [
            'label' => 'Rider Agreements',
            'categories' => [
                [
                    'slug' => 'rider_contract',
                    'agreement_code' => 'RIDER_CONTRACT',
                    'name' => 'Rider Contract',
                    'default_template_name' => 'Default Rider Contract',
                    'assigned_modules' => ['riders'],
                    'default_content_file' => 'agreements.defaults.rider_contract',
                ],
                [
                    'slug' => 'passport_agreement',
                    'agreement_code' => 'PASSPORT_AGREEMENT',
                    'name' => 'Passport Agreement',
                    'default_template_name' => 'Default Passport Agreement',
                    'assigned_modules' => ['riders'],
                    'default_content_file' => 'agreements.defaults.passport_agreement',
                ],
            ],
        ],
    ],
];
