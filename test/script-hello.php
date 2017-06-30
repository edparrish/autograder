<?php
include_once("../grader.php");
// Absolute path to student submissions
define("TEST_DIR", ROOT_DIR."/test/testfiles");

$students = null; // for all students
$students = array("cpp1", "cpp2"); // list student folders to test

class GradeRunner extends Grader {
    // Test commands to run for each student submission.
    function test() {
        deleteGlobRec("*.[eE][xX][eE]", $this->getDir()); // Windows
        deleteGlobRec("*.o", $this->getDir());   // Object files

        // 1. Find the test file
        $fileName = "hello.cpp";
        $glob = "[hH]el*.cpp";
        $contentRE = "/\bHell?o\b/";
        $testFile = $this->findClosestFile($fileName, $glob, $contentRE);
        $testName = basename($testFile);
        $this->writeGradeLog("Testing file $testName\n", $testName);

        // 2. Compile the test file
        $tc = new TestCompileCPP($testFile);
        $compiles = $this->fail($tc, -10);

        // 3. Run compiled program with defined input and save the output.
        if ($this->getProperty("compiles")) {
            // Find executable file
            $path = dirname($testFile);
            if ($path == NULL) $path =".";
            $baseName = basename($testFile, ".cpp");
            $exe = $path.DIRECTORY_SEPARATOR."$baseName.exe";
            // Execute with predefined input.
            $cmd = "$exe < ../../input/input1.txt"; //"
            $this->runLogCmd($cmd, "out1.log", true, 4);

        // 4. Check the output and comment on problems.
            $fc1 = new FileContents("out1.log");
            $fc1->removeLines(0, 3);
            // Compare student output against instructor's solution output
            $outFC1 = new FileContents("../../input/compare1.txt");
            $tc = new TestCompareFiles($fc1, $outFC1);
            $this->fail($tc, -6, "Wrong output for: $testFile");
            // More detailed test cases
            $tc = new TestMatch("/Hello/", $outFC1);
            $this->fail($tc, -1, "Output missing word \"Hello\"");
            $tc = new TestMatch("/Indigo/", $outFC1);
            $this->fail($tc, -5, "Output missing name");
        }

        // 5. Check the source code file for any required elements.
        $tfc = new FileContents($testFile);
        $tfc->stripComments();
        $tc = new TestMatch("/\bcin\b/", $tfc);
        $this->fail($tc, -5, "Missing cin statement", $testFile);

        // 6. Evaluate and score the combined test cases.
        if (!$testName) $testName = $fileName; // Ensure name to print
        $eval = new ValueEvaluator(10, 10, $compiles ? 1 : 0);
        $score = $this->report($eval, "Score for $testName:");

        // Total score and overall message
        $this->reportOverall(10);
    }
}
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
