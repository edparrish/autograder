<?php
/**
    All the evaluators as Evaluator and subclasses of Evaluator.

    @author Edward Parrish
    @version 1.3 7/15/04
    @version 1.4 11/09/15
    @version 1.5 06/19/17

Index to all evaluators:
CodeLabEvaluator($each, $eachLate = 0, $eachWrong = 0, $initScore = 0, $maxScore = NULL, $minScore = 0)
CompileEvaluator
LoadDbEvaluator
ReadmeEvaluator
StyleEvaluator
ValueEvaluator($initScore = 0, $maxScore = NULL, $minScore = 0, $multiplier = 1, $precision = 0)
PointMapEvaluator($pointScoreMap, $initScore = 0, $maxScore = NULL, $minScore = 0)
Ideas:
Evaluator can take a list of properties that map to a score if set.
PercentageEvalutor: Pass in a list of percentages that are the lowest for each bucket.
*/

/**
    Superclass for all evaluation classes.
    Provides a common interface for the Grader class.
 */
class Evaluator {
    var $initScore = 0;
    var $maxScore = 0;
    var $minScore = 0;

    /**
        Constructor for an Evaluator.

        @param $initScore Initial points to add or subtract values to.
        @param $maxScore Maximum possible score, which defaults to $initScore.
        @param $minScore Minimum possible score.
     */
    function Evaluator($initScore = 0, $maxScore = NULL, $minScore = 0) {
        $this->initScore = $initScore;
        if ($maxScore === NULL) {
            $this->maxScore = $initScore;
        } else {
            $this->maxScore = $maxScore;
        }
        $this->minScore = $minScore;
    }

    /**
        Returns the maximum score.

        @return The maximum score.
     */
    function getMaxScore() {
        return $this->maxScore;
    }

    /**
        Call to run the evaluation and return the score.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the TestResult data.
     */
    function score(&$tr, $sectionName) {
        assert(is_object($tr));
        if (get_class($tr) !== "TestResult") {
            die("tr is not a TestResult".get_class($tr));
        }
        return $this->evaluate($tr, $sectionName);
    }

    /**
        Runs the actual test and returns results in an TestResult.
        Implement the test in this function but call with function score().

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        echo "Override evaluate() function in subclasses";
        return 0;
    }
}

/**
    Calculates the score by summing the points assigned to each TestResult
    and subtracting from the initial score. Allows use of a multiplier for
    adjusting the score and rounding to a specified number of decimal places.
*/
class ValueEvaluator extends Evaluator {
    var $multiplier;
    var $precision;

    /**
        Constructor for a ValueEvaluator.

        @param $initScore Initial points to add or subtract values to.
        @param $maxScore Maximum possible score for the section.
        @param $minScore Minimum possible score.
        @param $multiplier Number by which to multiply the final score
        @param $precision Number of digits after the decimal point in the
        final score, which is used for rounding.
     */
    function ValueEvaluator($initScore = 0, $maxScore = NULL, $minScore = 0,
            $multiplier = 1, $precision = 0) {
        Evaluator::Evaluator($initScore, $maxScore, $minScore);
        $this->multiplier = $multiplier;
        $this->precision = $precision;
    }

    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        $score = $this->initScore + $tr->sumValues($sectionName);
        $score *= $this->multiplier;
        if ($score < $this->minScore) {
            $score = $this->minScore;
            //echo "Assigning minimum score: $this->minScore\n";
        }
        if ($this->maxScore > 0 and $score > $this->maxScore) {
            $score = $this->maxScore;
        }
        return round($score, $this->precision);
    }
}

/**
    Calculates the score based on compiler errors and warnings. Not compiling
    returns a score of zero. Warnings start with an initial score and
    subtracts one for each warning to a minimum of one point.
 */
class CompileEvaluator extends Evaluator {
    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        // Calculate score from non-compiler tests
        $score = $this->initScore + $tr->sumValues($sectionName);
        $compiles = $tr->getProperty("compiles");
        $warnings = $tr->getProperty("warnings");
        if (!$compiles) {
            $score = 0;
        } else if ($warnings > 1) { // sumValues takes off first point 10/31/16
            $score -= $warnings; // 1 point each warning
            if ($score < 1) $score = 1;
        }
        return $score;
    }
}


/**
    Calculates the score based on points assigned and README.txt errors.
 */
class ReadmeEvaluator extends Evaluator {
    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        // Calculate score from assigned test points
        $score = $this->initScore + $tr->sumValues($sectionName);
        if ($this->maxScore > 0 and $score > $this->maxScore) {
            $score = $this->maxScore;
        }
        if ($score < $this->minScore and $this->maxScore > 0) {
            $score =  $this->minScore;
        }
        if ($tr->getProperty("ReadmeExists") === false) {
            $score = 0;
        }
        return $score;
    }
}


/**
    Checks CodeLab results.
*/
class CodeLabEvaluator extends Evaluator {
    var $each;
    var $eachLate;
    var $eachWrong;

    /**
        Constructor for a CodeLabEvaluator.

        @param $each Points for each exercise completed on time.
        @param $eachLate Points for each exercise completed late.
        @param $eachWrong Points for each exercise completed but incorrect.
        @param $initScore Initial points to add or subtract values to.
        @param $maxScore The maximum possible score; Defaults to $initScore.
            <= 0 means no maximum. If set, missed points missed are subtracted
            from the $maxScore.
        @param $minScore The minimum possible score.
     */
    function CodeLabEvaluator($each, $eachLate = 0, $eachWrong = 0,
            $initScore = 0, $maxScore = NULL, $minScore = 0) {
        Evaluator::Evaluator($initScore, $maxScore, $minScore);
        $this->each = $each;
        $this->eachLate = $eachLate;
        $this->eachWrong = $eachWrong;
    }

    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        $each = $this->each;
        $eachLate = $this->eachLate;
        $eachWrong = $this->eachWrong;
        $totalProblems = $tr->getProperty("totalProblems");
        $correctOnTime = $tr->getProperty("correctOnTime");
        $correctLate = $tr->getProperty("correctLate");
        $wrong = $tr->getProperty("incorrect");
        $missingCodeLab = $tr->getProperty("missingCodeLab");
        //var_dump($totalProblems, $correctOnTime, $correctLate, $wrong, $missingCodeLab);
        //echo "each=$each, eachLate=$eachLate, eachWrong=$eachWrong\n";

        // Calculate max score
        $maxCodeLab = $totalProblems * max($each, $eachLate, $eachWrong);
        if ($this->maxScore <= 0) {
            $this->maxScore = $maxCodeLab;
        }

        // Calculate score from non-CodeLab exercises
        $score = $this->initScore + $tr->sumValues($sectionName);

        // Calculate CodeLab contribution to score
        $actualCodeLab = $correctOnTime * $each + $correctLate * $eachLate
            + $wrong * $eachWrong;
        $score = ceil($score - ($maxCodeLab - $actualCodeLab));
        if ($score > $this->maxScore) $score = $this->maxScore;
        if ($score < $this->minScore) $score = $this->minScore;
        //echo "score=$score, maxCodeLab=$maxCodeLab actualCodeLab=$actualCodeLab\n";

        // Additional messages
        if (1 == $missingCodeLab) {
            $msg = "Not registered with CodeLab";
            //$score = 0; // added 9/10/2012
            $tr->add($sectionName, "CodeLabTest", $msg, 0);
        } else if ($score < $this->maxScore) {
            $eachRound3 = round($each, 3);
            $msg = "CodeLab correct on time: $correctOnTime / $totalProblems (x$eachRound3 each)";
            $tr->add($sectionName, "CodeLabTest", $msg, 0);
        }
        if ($eachWrong !== 0 && $wrong > 0  && $score < $this->maxScore) {
            $eachWrongRound3 = round($eachWrong, 3);
            $msg = "CodeLab incorrect but attempted: $wrong ($eachWrongRound3 each)";
            $tr->add($sectionName, "CodeLabTest", $msg, 0);
        }
        if ($eachLate !== 0 && $correctLate > 0 && $score < $this->maxScore) {
            $eachLateRound3 = round($eachLate, 3);
            $msg = "CodeLab correct but late: $correctLate ($eachLateRound3 each)";
            $tr->add($sectionName, "CodeLabTest", $msg, 0);
        }

        return $score;
    }
}


/**
    Calculates the score based on points assigned and for programming style
    errors.
*/
// TODO: add a warnings list that will classify some errors as warnings
class StyleEvaluator extends Evaluator {
    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        // Calculate score from assigned test points
        $score = $this->initScore + $tr->sumValues($sectionName);
        if ($tr->messageExists("No source code files found")
                OR $tr->getProperty("no_files") == true) {
            $score = 0;
        } else {
            $errCount = $tr->count($sectionName);
            $score = $score - $errCount;
            if ($score < $this->minScore) $score = $this->minScore;
        }
        return $score;
    }
}


/**
    Calculates the score based on points assigned and modified by problems
    encountered loading a database.
 */
class LoadDbEvaluator extends Evaluator {
    /**
        Evaluates the score based on the TestResult list.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        $score = $this->initScore + $tr->sumValues($sectionName);
        if ($tr->getProperty("dbloaded") == false
                and $tr->messageExists("No SQL files to load")) {
            $score = 0;
        } else if ($tr->getProperty("dbloaded") == false) {
            $score = 1;
        }
        if ($score < 0) $score = 0;
        if ($score == 0 and $tr->getProperty("dbloaded") == true) {
            $score = 1;
        }
        return $score;
    }
}


/**
    Returns the $maxScore no mater the test results.
*/
class FullScoreEvaluator extends Evaluator {
    /**
        Returns the $maxScore no mater the test results.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        return $this->maxScore;
    }
}


/**
    Takes a map of (points=>scores) and maps the sum of points to produce
    the score using the map.

    Example:
    $pointScoreMap = array(10=>5, 9=>4, 6=>3, 4=>2, 1=>1);
    $score = $this->report(new PointMapEvaluator($pointScoreMap, 10),
        "Run and Match Score:");
*/
class PointMapEvaluator extends Evaluator {
    var $pointScoreMap;

    /**
        Constructor for a PointMapEvaluator.

        @param $pointScoreMap A map of minimum points to score values in the form (minimumPoints=>score).
        @param $maxScore Maximum possible score for the section.
        @param $initScore Initial points to add or subtract values to.
        @param $minScore Minimum possible score.
    */
    function PointMapEvaluator($pointScoreMap, $initScore = 0,
            $maxScore = NULL, $minScore = 0) {
        Evaluator::Evaluator($initScore, $maxScore, $minScore);
        $this->pointScoreMap = $pointScoreMap;
    }

    /**
        Returns the score for the sum of points based on the $pointScoreMap.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        $score = $this->initScore + $tr->sumValues($sectionName);
        //echo "score=$score\n";
        //var_dump($tr->getMessageList($sectionName));
        krsort($this->pointScoreMap); // Sort array by key in reverse order
        //echo "pointScoreMap after sort:";
        //var_dump($this->pointScoreMap);
        foreach ($this->pointScoreMap as $points=>$mappedScore) {
            //echo "points=$points, mappedScore=$mappedScore\n";
            if ($score >= $points) {
                if ($mappedScore >= $this->minScore) {
                    return $mappedScore;
                } else {
                    return $this->minScore;
                }
            }
        }
        return $this->minScore; // When all else fails
    }
}

/**
    Takes a map of (itemCount=>scores) and maps the sum of items to produce
    the score using the map.

    Example:
    $itemScoreMap = array(10=>5, 9=>4, 6=>3, 4=>2, 1=>1);
    $score = $this->report(new ItemMapEvaluator($itemScoreMap, 10),
        "Functional Score:");
*/
// NTR: not tested -- could be used for style?
class ItemMapEvaluator extends Evaluator {
    var $itemScoreMap;

    /**
        Constructor for a ValueEvaluator.

        @param $itemScoreMap A map of minimum items to score values in the form (minimumItems=>score).
        @param $maxScore Maximum possible score for the section.
        @param $initScore Initial points to add or subtract values to.
        @param $minScore Minimum possible score.
    */
    function ItemMapEvaluator($itemScoreMap, $initScore = 0,
            $maxScore = NULL, $minScore = 0) {
        Evaluator::Evaluator($initScore, $maxScore, $minScore);
        $this->itemScoreMap = $itemScoreMap;
    }

    /**
        Returns the score for the sum of points based on the $pointScoreMap.

        @param $tr The container for storing test results.
        @param $sectionName The section name for which to evaluate results.
        @return The score derived from the test data for the section.
     */
    function evaluate(&$tr, $sectionName) {
        $score = $this->initScore + $tr->count($sectionName);
        krsort($this->itemScoreMap);
        foreach ($this->itemScoreMap as $items => $mappedScore) {
            if ($score >= $items) {
                if ($mappedScore >= $this->minScore) {
                    return $mappedScore;
                } else {
                    return $this->minScore;
                }
            }
        }
        return $this->minScore; // When all else fails
    }
}
?>
