<?php

return [
    // Only for deliberate CLI replay of historical demo/tenant data migrations.
    // Never enables production DatabaseSeeder, stress seeding, or web provisioning.
    'allow_production_migrations' => filter_var(env('ALLOW_PRODUCTION_DEMO_MIGRATIONS', false), FILTER_VALIDATE_BOOLEAN),
];
