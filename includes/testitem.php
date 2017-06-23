<?php
/**
    Keeps track of a single test result.

    @author Ed Parrish
    @version 1.3 07/28/2008
    @version 1.4 05/16/2016
    @version 1.5 06/19/2017
*/
class TestItem {
    private $sectionName;
    private $testName;
    private $message;
    private $value;

    /**
        Constructor with parameters for the test.

        @param $sectionName The test section for this item.
        @param $testName The name of the test for this item.
        @param $msg The message for this item.
        @param $value The points for this item.
     */
    function TestItem($sectionName, $testName, $msg, $value) {
        $this->sectionName = $sectionName;
        $this->testName = $testName;
        $this->message = $msg;
        $this->value = $value;
    }

    /**
        Returns the message for this TestItem.

        @return the message for this TestItem.
     */
    function getMessage() {
        return $this->message;
    }

    /**
        Returns the section name for this TestItem.

        @return the section name for this TestItem.
     */
    function getSectionName() {
        return $this->sectionName;
    }

    /**
        Returns the test name for this TestItem.

        @return the test name for this TestItem.
     */
    function getTestName() {
        return $this->testName;
    }

    /**
        Returns the point value for this TestItem.

        @return the point value for this TestItem.
     */
    function getValue() {
        return $this->value;
    }
}

// Uncomment following line to run unit tests
//testTestItem();
function testTestItem() {
    echo "Testing TestItem\n";
    $sectionName = "Section name";
    $testName = "Test name";
    $msg = "Test message";
    $value = 10;
    echo "Testing constructor\n";
    $tr = new TestItem($sectionName, $testName, $msg, $value);
    echo "Testing getMessage\n";
    assert('$tr->getMessage() === $msg');
    echo "Testing getSectionName\n";
    assert('$tr->getSectionName() === $sectionName');
    echo "Testing getTestName\n";
    assert('$tr->getTestName() === $testName');
    echo "Testing getValue\n";
    assert('$tr->getValue() === $value');
    echo "...unit test complete\n";
}

?>
