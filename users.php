<?php
$users = [
    'headmaster' => [
        'password' => 'head123',
        'full_name' => 'Head Master Joseph',
        'role' => 'Head Master',
    ],
    'secondmaster' => [
        'password' => 'second123',
        'full_name' => 'Second Master Mary',
        'role' => 'Second Master',
    ],
    'academicmaster' => [
        'password' => 'academic123',
        'full_name' => 'Academic Master Samuel',
        'role' => 'Academic Master',
    ],
    'hod' => [
        'password' => 'hod123',
        'full_name' => 'Head of Departments Grace',
        'role' => 'Head of Departments',
        'department' => 'Science',
    ],
    'teacher1' => [
        'password' => 'teach123',
        'full_name' => 'Teacher Peter',
        'role' => 'Teacher',
        'department' => 'Science',
    ],
    'student1' => [
        'password' => 'study123',
        'full_name' => 'Student Amanda',
        'role' => 'Student',
        'class' => 'Form 1A',
    ],
];
return $users;
