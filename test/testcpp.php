<?php
// Test all C++ features
include_once("../grader.php");
// Absolute path to student submissions
define("TEST_DIR", ROOT_DIR.'/test/testfiles');
define("CHECK_CFG", ROOT_DIR.'/test/style-cpp.txt');
define("CODELAB_TABLE", "roster");

$students = null; // for all students
$students = array("cpp1", "cpp2"); // list student folders to test

// Predefined comments for manual review
define("STYLE_MSG_MANUAL", "  ** MANUAL CHECKS TODO **
 -I suggest you run Astyle.
");

define("OVERALL_MSG", "
Truly superior work!
Excellent work!
Overall, excellent work (including the extra credit).
Overall good work with a few minor problem areas.
Satisfactory progress with some missing work
Passable work with some missing pieces.
You are missing many parts of the assignment.
If you need help with an assignment, please ask.
I would like you to do well in this class.
I suggest you get tutoring or work with a partner.
");

class GradeRunner extends Grader {
    // Test commands to run for each student submission.
    function test() {
        deleteGlobRec("*.[eE][xX][eE]", $this->getDir()); // Windows
        assert(!fileExists("*.[eE][xX][eE]"));

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
        $eval = new CompileEvaluator(4);
        $score = $this->report($eval, "Compilation Score (4):");

        // 3. Run compiled program with defined input and save the output.
        if ($this->getProperty("compiles")) {
            // Find executable file
            $path = dirname($testFile);
            if ($path == NULL) $path =".";
            $baseName = basename($testFile, ".cpp");
            $exe = $path.DIRECTORY_SEPARATOR.$baseName;
            if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
                $exe .= ".exe";
            }
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
        if (!$testName) $testName = $fileName; // Ensure some name to print
        $eval = new ValueEvaluator(10, 10, $compiles ? 1 : 0);
        $score = $this->report($eval, "Score for $testName:");

        // Programming style
        $tfc->reload(); // restore stripped comments
        $path = dirname($tfc->getPathname());
        $this->pass(new TestCondition(substr_count($path, " ") > 0), -1,
            "Do NOT put spaces in folder names like you did with \"$path\"");
        $pass = $this->run(new TestStyleCPP($tfc, CHECK_CFG));
        $this->pass(new TestMatch("/@author\s+Ed\s+Parrish/i", $tfc),
            -1, "Please use your name as the author rather than mine", $pass);
        // Evaluate results
        $numIssues = $this->getSectionResultsCount();
        $score = $this->report(new ValueEvaluator(4, 4, $testFile ? 1 : 0),
            "Program Style Score:");
        $this->writeGradeLog(" -Nicely done!\n", 0 == $numIssues);
        $this->writeGradeLog(STYLE_MSG_MANUAL, $numIssues > 0 && $score > 0);

        // README.txt
        $this->run(new TestReadme($this));
        $readme = $this->getReadme();
        $author = strtolower($readme->getStudentName());
        $this->pass(new TestCondition($author === "ed parrish"), 0,
            "Use \"$firstName $lastName\" as Author instead of \"Ed Parrish\"", $readme->isReadme());
        $eval = new ReadmeEvaluator(2);
        $score = $this->report($eval, "README.txt Score (2):");

        // Subtotal score
        $subtotal = $this->getScore();
        $maxScore = $this->getMaxScore();
        $this->writeGradeLog("Subtotal Score: $subtotal out of $maxScore\n");

        // Extra credit
        $tc = new TestPairProgClaim($this);
        $isPP = $this->pass($tc, 2, "Used pair programming");
        $this->report(new ValueEvaluator(0, 2), "Extra Credit Score:");

        // Total score
        $superior = 100;
        if($isPP) $superior = 110;
        $good = "Overall good work with a few problem areas.";
        $sat = "Satisfactory overall with some problem areas.";
        $pass = "Passable overall with some problem areas.";
        if ($scrCL <= 2) {
            $good = "Good overall but would be better if you did the CodeLab.";
            $sat = "Satisfactory overall but would be better if you did the CodeLab.";
            $pass = "Passable overall but would be better if you did the CodeLab.";
        }
        $msg = "You are missing main parts of the assignment like: $fileName\nIf there was a problem, let me know right away.";
        if ($testFile) $msg = "You are missing many parts of the assignment";
        $comments = array(
            $superior=>"Truly superior work!",
            100=>"Excellent work!",
            90=>"Overall, excellent work!",
            80=>$good,
            70=>$sat,
            60=>$pass,
            10=>$msg,
            0=>"You are missing most parts of the assignment"
        );
        $this->reportOverall($maxScore, false, $comments, true);
        $this->writeGradeLog(OVERALL_MSG);
    }
}
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
