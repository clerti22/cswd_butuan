<?php

declare(strict_types=1);

$printConfig = [
    'mainTable' => 'car_table',
    'familyTable' => 'car_family_composition_table',
    'familyForeignKey' => 'car_id',
    'treatmentTable' => 'car_initial_treatment_plan_table',
    'treatmentForeignKey' => 'car_id',
    'formLabel' => 'CAR General Intake Sheet',
    'pageTitle' => 'CAR Print Form',
    'notFoundMessage' => 'CAR record not found.',
    'preparedBy' => '',
    'formCode' => 'CSWDD.F.053.REV01',
    'enableSignatoryEditor' => true,
    'printScript' => 'print-form.js',
];

require __DIR__ . '/intake_print_template.php';
