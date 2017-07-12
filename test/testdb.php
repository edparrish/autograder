<?php
// Test database grading with example student files
include_once("../grader.php");
// Absolute path to student submissions
define("TEST_DIR", ROOT_DIR.'/test/testfiles');

$students = null; // for all students
$students = array("db1", "db2"); // list student folders to test

class GradeRunner extends Grader {
    // Test commands to run for each student submission.
    function test() {
        // Load database
        $ff = new FileFinder("*.sql");
        $dbFile = $ff->findLargestFile();
        $this->run(new TestLoadDB($dbFile));
        $eval = new LoadDBEvaluator(4);
        $score = $this->report($eval, "Database Implementation Score:");

        // Test query 1
        $ff = new FileFinder("[qQ]*1.txt");
        $sqlFile = $ff->findFirstFile();
        $this->fail(new TestCondition($sqlFile), -2, "No query1.txt file");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $tc = new TestCondition($studentSql);
            $this->fail($tc, -2, "Empty query1.txt file");
            $tc = new TestRunLogSQL($studentSql, "query1.log");
            //$this->run($tc, $studentSql);
            $this->fail($tc, -1, "Error running query 1", $studentSql);
            $sql = "SELECT ProductID, PriceEach, Quantity
                    FROM orderitems
                    WHERE OrderID = 2";
            // Compare against required output
            $tc = new TestCompareSQL($sql, $studentSql);
            $this->fail($tc, -1, "Error in query 1", $studentSql);
        }
        // Test query 2
        $ff = new FileFinder("[qQ]*2.txt");
        $sqlFile = $ff->findFirstFile();
        $this->fail(new TestCondition($sqlFile), -2, "No query2.txt file");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $tc = new TestCondition($studentSql);
            $this->fail($tc, -2, "Empty query2.txt file");
            $tc = new TestRunLogSQL($studentSql, "query2.log");
            $this->fail($tc, -1, "Error running query 2", $studentSql);
            $sql = "SELECT SupplierName,purchorders.Qty,DateOrdered,ProductName
                FROM suppliers, products, purchorders
                WHERE suppliers.ID=products.SupplierID
                AND products.ID=purchorders.ProductID
                ORDER BY SupplierName, DateOrdered";
            // Compare against required output
            $tc = new TestCompareSQL($sql, $studentSql);
            $this->fail($tc, -1, "Error in query 2", $studentSql);
            // Check query for required elements.
            $pat = "/ORDER\s+BY\s+(suppliers\.)?SupplierName/i";
            $this->fail(new TestMatch($pat, $queryFC), 0,
                "-Query 2 missing clause: ORDER BY SupplierName", !$pass);
        }
        // Evaluate the results
        $score = $this->report(new ValueEvaluator(4), "SQL Queries Score:");
        $this->writeGradeLog(" -Well done!\n", 4 == $score);

        // README.txt
        $this->run(new TestReadme($this));
        $eval = new ReadmeEvaluator(2);
        $score = $this->report($eval, "README.txt Score (2):");

        // Subtotal score
        $subtotal = $this->getScore();
        $maxScore = $this->getMaxScore();
        $this->writeGradeLog("Subtotal Score: $subtotal of $maxScore\n");

        // Extra credit query 3
        $ff = new FileFinder("[qQ]*3.txt");
        $sqlFile = $ff->findFirstFile();
        $this->passFail(new TestCondition($sqlFile),
            1, "Submitted extra-credit query",
            0, "Did not turn in extra credit file: query3.txt");
        if ($sqlFile) {
            $queryFC = new FileContents($sqlFile);
            $queryFC->trimWhitespace();
            $studentSql = $queryFC->toString();
            $tc = new TestCondition($studentSql);
            $this->fail($tc, -1, "Empty query3.txt file", $sqlFile);
            $tc = new TestRunLogSQL($studentSql, "query3.log");
            $this->fail($tc, -1, "Error running XC query", $studentSql);
            $sql = "SELECT SupplierName,suppliers.ID,ProductName,products.ID
                    FROM suppliers LEFT JOIN products
                    ON suppliers.ID = products.SupplierID
                    WHERE products.ID IS NULL";
            $this->fail(new TestCompareSQL($sql, $studentSql, "test"),
                -1, "Error in extra credit query", $studentSql);
        }
        $this->report(new ValueEvaluator(0, 1), "Extra Credit:", true);

        // Total score
        $comments = array(
            110=>"Truly superior work!",
            100=>"Excellent work!",
            90=>"Overall, excellent work!",
            80=>"Overall good work with a few missing pieces.",
            70=>"Overall satisfactory with some problems and missing pieces.",
            60=>"Overall passable with some problems and missing pieces",
            50=>"Overall has errors and missing work that affected the score.",
            0=>"You are missing most parts of the assignment"
        );
        $this->reportOverall($maxScore, false, $comments);
    }
}
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
