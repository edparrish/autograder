<?php
include_once("../grader.php");
// Absolute path to student submissions
define("TEST_DIR", ROOT_DIR."/test/testfiles");
// Other string constants defined here

$students = null; // for all students
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
