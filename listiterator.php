<?php
define("OFF_LIST", -1);

/**
    Iterates through all the items on the list.

    @author Edward Parrish
    @version 2 6/19/2017
*/
class ListIterator {
    var $list;
    var $index = OFF_LIST;

    /**
     * Constructor for an ErrorList object.
     */
    function ListIterator($list) {
        $this->list = $list;
    }

    /**
     * Returns true if there are more items on the list.
     */
    function hasNext() {
        if ($this->index == OFF_LIST) {
            return count($this->list) != 0;
        } else {
            return $this->index != count($this->list) - 1;
        }
    }

    /**
     * Updates the location to the next error on the list.
     *
     * @return The next error list item.
     */
    function next() {
        $this->index++;
        return $this->list[$this->index];
    }

    /**
     * Returns the current item on the list.
     */
    function current() {
        return $this->list[$this->index];
    }
}

// Uncomment following line to run unit tests
//testListIterator();
function testListIterator() {
    $list = array("one", "two", "three", "four");
    echo "Testing constructor, hasNext, next\n";
    $it = new ListIterator($list);
    $index = 0;
    while ($it->hasNext()) {
        $item = $it->next();
        assert('!strcmp($item, $list[$index])');
        assert('$item === $list[$index]');
        $index++;
    }
    assert('$index === count($list)');
    echo "Testing count\n";
    $it2 = new ListIterator($list);
    while ($it2->hasNext()) {
        $item = $it2->next();
        assert('$item === $it2->current()');
    }
    echo "...unit test complete\n";
}
?>
