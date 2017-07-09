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

        // Programming style
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
        $this->writeGradeLog("Subtotal Score: $subtotal of $maxScore\n");

        // Extra credit
        $tc = new TestPairProgClaim($this);
        $isPP = $this->pass($tc, 2, "Used pair programming");
        // Lightbot XC
        $this->testLightbot(2);
        // Test file: cakeredux.cpp
        $xtraName = "cakeredux.cpp";
        $glob = "[Cc]ake[Rr]*.cpp"; // acceptable but distinct glob
        $contentRE = "/\bMint/i";
        $xtraFile = $this->findClosestFile($xtraName, $glob, $contentRE);
        if ($xtraFile) {
            $xtraBaseName = basename($xtraFile);
            $this->run(new TestCondition(true,
                "Added extra credit file: $xtraBaseName", 2));
            $numIssues = $this->getSectionResultsCount();
            $this->testCakeRedux(2, $xtraName, $xtraFile);
            $numIssues = $this->getSectionResultsCount() - $numIssues;
            $this->run(new TestCondition(!$numIssues, "-Nice cake code!", 0));
        }
        $this->report(new ValueEvaluator(0, 6), "Extra Credit Score:", true);
        $this->writeGradeLog(XTRA_MSG);

        // Total score
        $superior = 116;
        if($isPP) $superior = 123;
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
        $this->reportOverall($maxScore, false, $comments);
        $this->writeGradeLog(OVERALL_MSG);
    }

    function testLightbot($pts) {
        $readme = $this->getReadme();
        $readmeFC = $readme->getReadmeFileContents();
        $rmExists = $readme->isReadme();
        $pat = "/mind\s+(is\s+)?get.*numb/i";
        $level9 = $this->pass(new TestMatch($pat, $readmeFC), $pts,
            "Completed game level 9+", $rmExists);
        $pat = "/Now.*thinking\s+like.*programmer/i";
        $level8 = $this->pass(new TestMatch($pat, $readmeFC), $pts - 1,
            "Completed game level 8", $rmExists && !$level9);
        $pat = "/Put.*back.*functions/i";
        $level7 = $this->pass(new TestMatch($pat, $readmeFC), $pts - 1,
            "Completed game level 7", $rmExists && !($level8 || $level9));
    }

    function testCakeRedux($pts, $xtraName, $xtraFile) {
        // Compile
        $baseName = $this->checkCompile($pts, $xtraName, $xtraFile);
        $compiles = $this->getProperty("compiles");
        if (!$compiles) return; // return early to prevent other messages
        // Check source code for required elements
        $tfc = new FileContents($xtraFile);
        $tfc->stripComments();
        $multiInputRE = "/\bcin\s*\>\>\s*\w+\s*\>\>\s*\w+/";
        $isMultiIn = $this->pass(new TestMatch($multiInputRE, $tfc), -2,
            "Cannot have more than one cake input variable (spec 1-3)\n --!!Must manual test!!", $xtraFile);
        $this->fail(new TestMatchCount("/\bcin\b/", $tfc, 0, 2), -1,
            "Cannot have more than two cin statements (spec 1-3)", $xtraFile);
        $re = "/\w+\.substr\s*\(/";
        $this->fail(new TestMatch($re, $tfc), -1,
            "Need substr() for the problem (spec 1-4)", $xtraFile);
        $hasWhile = $this->fail(new TestMatch("/\bwhile\b/", $tfc), -1,
            "Missing while loop (spec 1-5)", $xtraFile);
        if ($compiles) {
            $exe = "$baseName.exe";
            // First run
            $this->copyFile("../../solutions/asn06/p4r1doc.log", "p4r1doc.log");
            $testCmd = "$exe < ../../solutions/asn06/p4r1_cake.txt"; //"
            $this->runLogCmd($testCmd, "p4r1out.log", $xtraFile, 2);
            // Second run
            $this->copyFile("../../solutions/asn06/p4r2doc.log", "p4r2doc.log");
            $testCmd2 = "$exe < ../../solutions/asn06/p4r2_cake.txt";//"
            $this->runLogCmd($testCmd2, "p4r2out.log", $xtraFile, 2);
            // Third run
            $this->copyFile("../../solutions/asn06/p4r3doc.log", "p4r3doc.log");
            $testCmd2 = "$exe < ../../solutions/asn06/p4r3_cake.txt";//"
            $this->runLogCmd($testCmd2, "p4r3out.log", $xtraFile, 2);
            // Check the output for errors
            $fc1 = new FileContents("p4r1out.log");
            $fc2 = new FileContents("p4r2out.log");
            $fc3 = new FileContents("p4r3out.log");
            //$fcList = array($fc1, $fc2, $fc3);
            $this->pass(new TestMatch("/Killing process/i", $fc1), -1,
                "Process timeout entering: CM12");
            $this->pass(new TestMatch("/stackdumpfile/i", $fc1), -1,
                "Stack dump error entering: CM12 n");
            $this->pass(new TestMatch("/stackdumpfile/i", $fc2), -1,
                "Stack dump error entering: PC999 n");
            $this->pass(new TestMatch("/stackdumpfile/i", $fc3), -1,
                "Stack dump error entering: T1 n");
            // Check the output of all runs
            $fc1->removeLines(0, 3);
            $pat = "/12\s*Ch\w+\s+Mint\s+cak\w+/i";
            $pass = $this->fail(new TestMatch($pat, $fc1), -1,
                "Input CM12 did not output: 12 Chocolate Mint cakes (spec 1, 5)");
            $this->run(new TestCondition(!$pass,
                "Your output: [".$fc1->toString()."]"));
            $this->fail(new TestMatch("/[$ :l]419\.88/", $fc1), -1,
                "Missing/wrong order total for CM12, s/b 419.88 (spec 3,4,5)");
            $fc2->removeLines(0, 3);
            $pat = "/999\s+Pum\w+\s+Che\w+/i";
            $pass = $this->fail(new TestMatch($pat, $fc2), -1,
                "Input PC999 did not output: 999 Pumpkin Cheesecakes (spec 1, 5)");
            $this->run(new TestCondition(!$pass,
                "Your output: [".$fc2->toString()."]"));
            $this->fail(new TestMatch("/[$ :l]34955\.01/", $fc2), -1,
                "Missing/wrong order total for PC999, s/b 34955.01 (spec 3,4,5)");
            $fc3->removeLines(0, 3);
            $pat = "/1\s*Tiram\w+\s+cak\w+/i";
            $pass = $this->fail(new TestMatch($pat, $fc3), -1,
                "Input T1 did not output: 1 Tiramisu cakes (spec 1, 6)");
            $this->run(new TestCondition(!$pass,
                "Your output: [".$fc3->toString()."]"));
            $this->fail(new TestMatch("/[$ :l]34\.99/", $fc3), -1,
                "Missing/wrong order total for T19, s/b 34.99 (spec 2,3)");
        }
        $this->pass(new TestMatch("/case\s*['\"\w]+\s*:/", $tfc), 0,
            "POSSIBLE CHEATING DETECTED: use of case", $xtraFile);
        $this->pass(new TestMatch("/\w+\.rbegin\s*\(/", $tfc), 0,
            "POSSIBLE CHEATING DETECTED: use of rbegin() iterator", $xtraFile);
        $this->pass(new TestMatch("/\w+\.back\s*\(/", $tfc), 0,
            "POSSIBLE CHEATING DETECTED: use of back()", $xtraFile);
        $this->pass(new TestMatch("/\w+\.find_first_of\s*\(/", $tfc), 0,
            "POSSIBLE CHEATING DETECTED: use of find_first_of()", $xtraFile);
    }


    /**
        Checks for $fileName existance and compiles the file.

        @param $pts Number of points for the project
        @param $fileName Specified file name
        @param $testFile file pathname used by the student
    */
    function checkCompile($pts, $fileName, $testFile) {
        // Check name
        $isFile = $testFile && fileExists($testFile);
        $this->fail(new TestCondition($isFile), -$pts,
            "$fileName was not turned in :(");
        $baseName = basename($testFile);
        $fileBase = basename($fileName, ".cpp");
        $this->fail(new TestFileExists("$fileBase*.cpp"), 0,
            "Wrong file name: $baseName, should be: $fileName\n --You must use the specified file name\n --I did not take off this time", $testFile);
        // Check spaces in file name
        $this->pass(new TestCondition(substr_count($baseName, " ") > 0), -1,
            "1:Do NOT put spaces in file names like \"$baseName\"\n --Spaces make it harder to compile from the command line.");
        $origName = ""; // flag if renaming needed.
        // Rename if spaces in file path
        if (substr_count($baseName, " ") > 0) {
            $path = dirname($testFile);
            if ($path == NULL) $path =".";
            $cwd = getcwd();
            chdir($path); // In case of spaces in dir names
            $origName = $baseName;
            $newName = str_replace(' ', '', $origName);
            rename($origName, $newName);
            chdir($cwd); // return to original working dir
            $origPathName = $testFile;
            $testFile = $path.DIRECTORY_SEPARATOR.$newName;
        }
        $testBase = basename($testFile, ".cpp"); // file of file.ext
        // Strip .cpp if student has multiple extensions.
        $base = $testBase;
        while (strrpos($base, ".cpp")) {
            $base = basename($base, ".cpp");
        }
        $this->fail(new TestCondition($base === $testBase), -1,
            "1:Too many .cpp extensions");
        // Compile
        $compiles = $this->fail(new TestCompileCPP($testFile), -$pts,
            "Did not compile--code must compile for a good score", $isFile);
        $warnings = $isFile && $this->getProperty("warnings");
        $this->pass(new TestCondition($warnings), -1,
            "Your code must compile without warnings", $isFile);
        // Return to original name if file was renamed
        if ($origName != "") rename($testFile, $origPathName);
        return $base;
    }
}
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
