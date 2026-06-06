<?php
require_once __DIR__ . '/data_store.php';

$defaultSubjects = [
    'MATH101' => ['name' => 'Mathematics', 'teacher' => 'teacher1'],
    'ENG101' => ['name' => 'English', 'teacher' => 'teacher1'],
    'BIO101' => ['name' => 'Biology', 'teacher' => 'teacher1'],
];

$defaultEnrollments = [
    'student1' => ['MATH101', 'ENG101', 'BIO101'],
];

return load_json_data('subjects', [
    'subjects' => $defaultSubjects,
    'enrollments' => $defaultEnrollments,
]);
