<?php
// Simple test of the system after installation.
require_once("../grader.php");
// Report all test problems
error_reporting(E_ALL | E_STRICT);
// Absolute path to "student" test files
define("TEST_DIR", ROOT_DIR."/test/testfiles");

// Array of students to test; null for all students.
$students = array("cpp1"); // list student folders to test

class GradeRunner extends Grader {
    // Setup test environment to run before any grading.
    function startTest() {
        parent::startTest();
        echo "Running startTest\n";
    }

    // Test commands to run for each student submission.
    function test() {
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();
        echo "\nRunning test() for student $firstName $lastName\n";
    }

    // Tear down test environment after all tests
    function finishTest() {
        parent::finishTest();
        echo "Running finishTest\n";
    }
}
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
