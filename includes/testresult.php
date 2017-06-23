<?php
if (file_exists('../ag-config.php')) include_once '../ag-config.php';
require_once ROOT_DIR.'/includes/listiterator.php';
include_once  ROOT_DIR.'/includes/testitem.php';

/**
    Keeps track of student errors with messages and points.

    @author Ed Parrish
    @version 1.3 01/23/2008
    @version 1.4 08/04/2008
    @version 1.5 06/19/2017
*/
class TestResult {
    private $index = OFF_LIST;
    private $list = array();
    private $properties = array();

    /**
        Adds messages to the test result list.

        @param $sectionName The section name for the test result.
     */
    function add($sectionName, $testName, $msg = "", $value = 0) {
        //if (!$msg) $msg = "Completed $testName";
        $this->list[] =
            new TestItem($sectionName, $testName, $msg, $value);
    }

    /**
        Returns the current number of test results.

        @param $sectionName The section name for the test result.
     */
    function count($sectionName = "") {
        if ($sectionName === "") {
            return count($this->list);
        }
        $numItems = 0;
        foreach ($this->list as $item) {
            if ($sectionName === $item->getSectionName()) {
                $numItems++;
            }
        }
        return $numItems;
    }

    /**
        Returns a list of test messages as an array.

        @param $sectionName The section name for the test result.
     */
    function getMessageList($sectionName = "") {
        $messages = array();
        foreach ($this->list as $item) {
            if ($sectionName === ""
                    or $sectionName === $item->getSectionName()) {
                $messages[] = $item->getMessage();
            }
        }
        return $messages;
    }

    /**
        Returns a sum of the values.
     */
    function sumValues($sectionName = "") {
        $sum = 0;
        foreach ($this->list as $item) {
            if ($sectionName === ""
                    or $sectionName === $item->getSectionName()) {
                $sum += $item->getValue();
            }
        }
        return $sum;
    }

    /**
        Returns true if a message matches any part of the $message string.

        @param $message The string to use in the search.
        @param $caseMatters Set true if case matters.
     */
    function messageExists($message, $caseMatters = false) {
        if (!$caseMatters) $message = strtolower($message);
        $found = false;
        $size =  count($this->list);
        $i = 0;
        while ($i < $size and !$found) {
            $item = $this->list[$i];
            $msg = $item->getMessage();
            if (!$caseMatters) $msg = strtolower($msg);
            if (substr_count($msg, $message) != 0) {
                $found = true;
            }
            $i++;
        }
        return $found;
    }

    /**
        Returns true if a message matches the $pattern regular expression.

        @param $pattern The regular expression to use in the search.
     */
    function messageExistsRE($pattern) {
        $found = false;
        $size =  count($this->list);
        $i = 0;
        while ($i < $size and !$found) {
            $item = $this->list[$i];
            $msg = $item->getMessage();
            if (preg_match($pattern, $msg)) {
                $found = true;
            }
            $i++;
        }
        return $found;
    }

    /**
        Remove test results for the $sectionName or the entire list if
        no $section name is specified.
     */
    function reset($sectionName = "") {
        if ($sectionName === "") {
            $this->list = array();
            return;
        }
        $list = $this->list;
        $this->list = array();
        foreach($list as $item) {
            if ($sectionName !== $item->getSectionName()) {
                $this->list[] = $item;
            }
        }
    }

    /**
        Returns an iterator over test results.
     */
    function iterator() {
        return new ListIterator($this->list);
    }

    /**
        Returns true if the property has been set, otherwise false..
     */
    function containsKey($name) {
        return isset($this->properties[$name]);
    }

    /**
        Returns the value of a property or NULL if not set.

        @param $name The name of the property.
        @return the value of a property or NULL if not set.
     */
    function getProperty($name) {
        if ($this->containsKey($name)) {
            return $this->properties[$name];
        } else {
            return NULL;
        }
    }

    /**
        Removes a property and its value.

        @param $name The name of the property.
     */
    function removeProperty($name) {
        unset($this->properties[$name]);
    }

    /**
        Sets the value of a property.

        @param $name The name of the property.
        @param $value The value of the property.
     */
    function setProperty($name, $value) {
        $this->properties[$name] = $value;
    }
}

// Uncomment following line to run unit tests
//testTestResult();
function testTestResult() {
    echo "Testing TestResult\n";
    echo "Testing constructor\n";
    $tr = new TestResult();

    echo "Testing add\n";
    $tr->add("section1", "Test1", "Test message", 1);
    $tr->add("section2", "Test2", "Test message2", 2);
    $tr->add("section2", "Test3", "Test message3", 3);

    echo "Testing count\n";
    assert('$tr->count() === 3');
    assert('$tr->count("section1") === 1');

    echo "Testing sumValues\n";
    assert('$tr->sumValues() === 6');
    assert('$tr->sumValues("section2") === 5');

    echo "Testing getMessageList\n";
    assert('count($tr->getMessageList()) === 3');
    assert('count($tr->getMessageList("section1")) === 1');

    echo "Testing messageExists\n";
    if (!$tr->messageExists("Test message", true)) {
        echo "Error: did not find an existing message!\n";
    }
    if ($tr->messageExists("Test message bogus")) {
        echo "Error: found a non-existant message!\n";
    }

    echo "Testing messageExistsRE\n";
    if (!$tr->messageExistsRE("/Test message/i")) {
        echo "Error: did not find an existing regex message!\n";
    }
    if ($tr->messageExistsRE("/Test message bogus/")) {
        echo "Error: found a non-existant regex message!\n";
    }

    echo "Testing containsKey\n";
    if ($tr->containsKey("compiles")) {
        echo "Error: properties in wrong initial state!\n";
    }

    echo "Testing setProperty\n";
    $tr->setProperty("compiles", true);
    if (!$tr->containsKey("compiles")) {
        echo "Error: properties in wrong final state!\n";
    }

    echo "Testing getProperty\n";
    if ($tr->getProperty("compiles") != true) {
        echo "Error: wrong properties returned!\n";
    }

    echo "Testing removeProperty\n";
    $tr->removeProperty("compiles");
    if ($tr->containsKey("compiles")) {
        echo "Error: properties not removed!\n";
    }

    echo "Testing iterator (shows values):\n";
    $it = $tr->iterator();
    while ($it->hasNext()) {
        $item = $it->next();
        assert('$item !== NULL');
        print_r($item);
    }
    $tr->reset("section1");
    assert('$tr->count() === 2');
    $it2 = $tr->iterator();
    while ($it2->hasNext()) {
        $item = $it2->next();
        assert('$item !== NULL');
        //var_dump($item);
    }
    $tr->reset();
    assert('$tr->count() === 0');

    echo "...unit test complete\n";
}
?>
