<?php
// Test all database features
include_once("../grader.php");
// Absolute path to student submissions
define("TEST_DIR", ROOT_DIR.'/test/testfiles');
define("CODELAB_TABLE", "roster");

$students = null; // for all students
$students = array("db1", "db2"); // list student folders to test

class GradeRunner extends Grader {
    // Setup test environment to run before any grading.
    function startTest() {
        parent::startTest();
        echo "Running startTest\n";
    }

    // Test commands to run for each student submission.
    function test() {
        // Database query tests
        $globs = array("[qQ]*1.*", "[qQ]*2.*", "[qQ]*3.*", "[qQ]*4.*");
        $ff = new Filefinder($globs);
        $path = dirname($ff->findLargestFile());
        $this->pass(new TestCondition(substr_count($path, " ") > 0), -1,
            "Do not put spaces in folder names like you did with \"$path\"");
        $fcList = FileContents::toFileContents($ff);
        $this->pass(new TestMatchAny("/\brtf/", $fcList), -1,
            "Please submit plain text and NOT RTF files", $fcList);

        // Query 1 credit for any attempt
        $this->fail(new TestFileExists("[qQ]*1.*"), -2,
            "No query1.txt file");
        // Query 2 credit for any attempt
        $this->fail(new TestFileExists("[qQ]*2.*"), -2,
            "No query2.txt file");
        // Query 3 find most likely file
        $ff = new FileFinder("[qQ]*3.*");
        $ff->filterName("/\.log/i");
        $ff->filterName("/\.7z/i");
        $ff->filterName("/\.zip/i");
        $ff->filterName("/\.rar/i");
        $sqlFile = $ff->findFirstFile();
        $this->fail(new TestCondition($sqlFile), -2, "No query3.txt file");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $this->fail(new TestCondition($studentSql), -2,
                "Empty query3.txt file", $sqlFile);
            $this->run(new TestRunLogSQL($studentSql, "query3.log",
                "artzy"), $studentSql);
            $sql = "SELECT ProductID, PriceEach, Quantity
                    FROM orderitems
                    WHERE OrderID = 2";
            // Compare against required output
            $this->fail(new TestCompareSQL($sql, $studentSql, "artzy"),
                -1, "Error in query 3", $studentSql);
        }
        // Query 4 find most likely file
        $ff = new FileFinder("[qQ]*4.*");
        $ff->filterName("/\.log/i");
        $ff->filterName("/\.7z/i");
        $ff->filterName("/\.zip/i");
        $ff->filterName("/\.rar/i");
        $sqlFile = $ff->findFirstFile();
        $this->fail(new TestCondition($sqlFile), -2, "No query4.txt file");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $this->fail(new TestCondition($studentSql), -2,
                "Empty query4.txt file", $sqlFile);
            $this->run(new TestRunLogSQL($studentSql, "query4.log",
                "artzy"), $studentSql);
            $sql = "SELECT SupplierName,purchorders.Qty,DateOrdered,ProductName
                FROM suppliers, products, purchorders
                WHERE suppliers.ID=products.SupplierID
                AND products.ID=purchorders.ProductID
                ORDER BY SupplierName, DateOrdered";
            // Compare against required output
            $pass = $this->fail(new TestCompareSQL($sql, $studentSql, "artzy"),
                -1, "Error in query 4", $studentSql);
            // Check query for required elements.
            $pat = "/ORDER\s+BY\s+(suppliers\.)?SupplierName/i";
            $this->fail(new TestMatch($pat, $queryFC), 0,
                "-Query 4 missing clause: ORDER BY SupplierName", !$pass);
        }
        // Evaluate the results
        $score = $this->report(new ValueEvaluator(8), "SQL Queries Score:");
        $this->writeGradeLog(" -Well done!\n", 8 == $score);

        // README.txt
        $this->run(new TestReadme($this));
        $eval = new ReadmeEvaluator(2);
        $score = $this->report($eval, "README.txt Score (2):");

        // Subtotal score
        $subtotal = $this->getScore();
        $maxScore = $this->getMaxScore();
        $this->writeGradeLog("Subtotal Score: $subtotal of $maxScore\n");

        // Extra credit query 5
        $ff = new FileFinder("[qQ]*5.*");
        $ff->filterName("/\.7z/i");
        $ff->filterName("/\.zip/i");
        $ff->filterName("/\.rar/i");
        $sqlFile = $ff->findFirstFile();
        $this->passFail(new TestCondition($sqlFile),
            1, "Submitted extra-credit query",
            0, "Did not turn in extra credit file: query5.txt");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $this->fail(new TestCondition($studentSql), -1,
                "Empty query5.txt file", $sqlFile);
            $this->run(new TestRunLogSQL($studentSql, "query5.log",
                "artzy"), $studentSql);
            $sql = "SELECT SupplierName,suppliers.ID,ProductName,products.ID
                    FROM suppliers LEFT JOIN products
                    ON suppliers.ID = products.SupplierID
                    WHERE products.ID IS NULL";
            $this->fail(new TestCompareSQL($sql, $studentSql, "artzy"),
                -1, "Error in extra credit query", $studentSql);
        }
        $this->report(new ValueEvaluator(0, 1), "Extra Credit:", true);

        // Total score
        $comments = array(
            110=>"Truly superior work!",
            100=>"Excellent work!",
            90=>"Overall, excellent work!",
            80=>"Overall good work with a few missing pieces.",
            70=>"Overall satisfactory work with some problems and missing pieces.",
            60=>"Overall passable work with several problems and missing pieces",
            50=>"Overall you had some errors and missing work that affected your score.",
            20=>"You are missing the main part of the assignment.",
            0=>"You are missing most parts of the assignment"
        );
        $this->reportOverall($maxScore, false, $comments);
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
