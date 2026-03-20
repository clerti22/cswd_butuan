<?php

declare(strict_types=1);

$printConfig = [
    'mainTable' => 'cicl_table',
    'familyTable' => 'cicl_family_composition_table',
    'familyForeignKey' => 'cicl_id',
    'treatmentTable' => 'cicl_initial_treatment_plan_table',
    'treatmentForeignKey' => 'cicl_id',
    'formLabel' => 'CICL General Intake Sheet',
    'pageTitle' => 'CICL Print Form',
    'notFoundMessage' => 'CICL record not found.',
    'preparedBy' => '',
    'formCode' => 'CSWDD.F.053.REV01',
];

require __DIR__ . '/../shared/intake_print_template.php';
