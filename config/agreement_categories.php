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
                    'name' => 'Rider Contract',
                    'default_template_name' => 'Default Rider Contract',
                    'default_content_file' => 'agreements.defaults.rider_contract',
                ],
                [
                    'slug' => 'passport_agreement',
                    'name' => 'Passport Agreement',
                    'default_template_name' => 'Default Passport Agreement',
                    'default_content_file' => 'agreements.defaults.passport_agreement',
                ],
            ],
        ],
    ],
];
