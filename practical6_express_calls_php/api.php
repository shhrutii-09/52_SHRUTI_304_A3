
<?php
// Set response type to JSON
header("Content-Type: application/json");

// Sample student data
$students = [
    [
        "id" => 1,
        "name" => "Rahul Patel",
        "course" => "MSc ICT"
    ],
    [
        "id" => 2,
        "name" => "Priya Shah",
        "course" => "BCA"
    ],
    [
        "id" => 3,
        "name" => "Amit Desai",
        "course" => "BSc IT"
    ]
];

// Return JSON response
echo json_encode([
    "message" => "Students fetched successfully",
    "students" => $students
]);
?>
