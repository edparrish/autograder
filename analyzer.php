<?php
/**
    Basic statistics on homework by student.

    @author Edward Parrish
    @version 1.0 06/21/04
    @version 1.3 02/12/05
    @version 1.4 06/19/17
    TODO: update
*/
define("TEST_DIR", "C:/Courses/tools/autograde5/testfiles");
$students = null;

include("grader.php");

// Put the tests here but call grade() to run tests on all students
class Analyzer extends Grader {
  var $extra = 0;
  var $countExtra = 0;
  var $totalExtra = 0;

  // skip summary.log
  function startTest() {
    $this->timestamp = mktime();
    echo "Tabulating results:\n";
    echo "Student \tSc\tEC\tTtl\tHours\n";
  }

  // skip grade.log
  function preTest() {}

  function test() {
    file_exists($this->gradeLogName)
        or die("\nYou need to grade before running an analysis!\n\n");
    $base = basename("$this->dir");
    echo $base;
    if (strlen($base) < 8) echo "\t";

    $content = file_get_contents($this->gradeLogName);
    $pattern = "/Subtotal Score:\s*([0-9.]+)/i";
    preg_match($pattern, $content, $matches);
    if (isset($matches[1])) {
      $this->score = $matches[1];
      $this->totalScore += $matches[1];
      $this->countScore++;
      echo "\t$this->score";
    }

    $pattern = "/Extra Credit:\s*([0-9.]+)/i";
    preg_match($pattern, $content, $matches);
    if (isset($matches[1])) {
      $this->extra = $matches[1];
      $this->totalExtra += $matches[1];
      $this->countExtra++;
      echo "\t$this->extra";
    }
    echo "\t".($this->score + $this->extra);

    $readme = new Readme();
    $hours = $readme->getHoursClaim();
    if ($hours == 0) {
      echo "\tN/A";
    } else {
      $this->hours = $hours;
      $this->totalHours += $hours;
      $this->countHours++;
      echo "\t$hours";
    }
    echo "\n";
  }

  // skip grade.log
  function postTest() {}

  // skip summary.log
  function finishTest() {
    echo "Elapsed time: ".(mktime() - $this->timestamp)." seconds\n";
    echo "\nStudents graded: ".($this->countScore)."\n";
    if ($this->countScore != 0) {
      echo "Average score: ".($this->totalScore / $this->countScore)."\n";
    } else {
      echo "Average score: 0\n";
    }
    if ($this->countExtra != 0) {
      echo "Average extra: ".($this->totalExtra / $this->countExtra)."\n";
    } else {
      echo "Average extra: 0\n";
    }
    if ($this->countScore != 0) {
      echo "Average total: ".(($this->totalScore + $this->totalExtra) / $this->countScore)."\n";
    } else {
      echo "Average total: 0\n";
    }
    echo "\nStudents reporting hours: ".($this->countHours)."\n";
    if ($this->countHours != 0) {
      echo "Average hours: ".($this->totalHours / $this->countHours)."\n\n";
    } else {
      echo "Average hours: 0\n";
    }
  }
}

if ($argc == 2) {
  $testDir = $argv[1];
} else if (defined('TEST_DIR')) {
  $testDir = TEST_DIR;
} else {
  showUsage();
}

$analyzer = new Analyzer($testDir, $students);
$analyzer->runTest();

function showUsage() {
?>
This script analyzes statistics for each student after grading.

  Usage:
  <?php echo $argv[0]; ?>

  <option> path\to\test\directory
  With the --help, -help, -h, or -? options, you can get this help.

<?php
}
?>
