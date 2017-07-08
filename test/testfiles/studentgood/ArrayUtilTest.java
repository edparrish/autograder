/**
 * CS-20  Asg4
 * ArrayUtilTest.java
 * Purpose: Unit test of the ArrayUtil class.
 *
 * @version 1.3 3/13/05
 * @author Ed Parrish
 */
public final class ArrayUtilTest {
    private static int numErrors;

    /**
     * Private constructor to prevent instantiating this utility class.
     */
    private ArrayUtilTest() { }

    /**
     * The main method begins execution of the tests.
     *
     * @param args not used
     */
    public static void main(final String[] args) {
        testMakeArray();
        testCopyArray();
        testBubbleSort();
        testBubbleSortPlus();
        testLinearSearch();
        testBinarySearch();
        testShowArray();
        testShowArray2();
        testRunTests();
        if (numErrors == 0) {
            System.out.println("*** All tests passed ***");
        }
        System.exit(0);
    }

    /**
     * Convenience method to test for assertions.
     *
     * @param condition The test condition that must be true to pass.
     * @param message The reason for the failure.
     */
    public static void assertTrue(boolean condition, String message) {
        if (!condition) {
            //throw new RuntimeException(message);
            System.out.println("Error: " + message);
            numErrors++;
        }
    }

    /**
     * Test method: int[] makeArray(int)
     */
    public static void testMakeArray() {
        System.out.println("Testing makeArray");
        final int size = 10;
        int[] array = ArrayUtil.makeArray(size);
        assertTrue(array.length == size, "makeArray makes wrong array length");
        for (int i = 0; i < array.length; i++) {
            assertTrue(array[i] >= ArrayUtil.LOW,
                "makeArray has array value too low");
            assertTrue(array[i] < ArrayUtil.HIGH,
                "makeArray has array value too high");
        }
        int[] array2 = ArrayUtil.makeArray(size);
        int count = 0;
        for (int i = 0; i < array.length; i++) {
            assertTrue(array.length == array2.length,
                "makeArray has various lengths");
            if (array[i] == array2[i]) count++;
        }
        assertTrue(count < size,
            "makeArray does not produce random elements");
    }

    /**
     * Test method: int[] copyArray(int[])
     */
    public static void testCopyArray() {
        System.out.println("Testing copyArray");
        int[] a = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10};
        int[] a2 = ArrayUtil.copyArray(a);
        assertTrue(a.length == a2.length, "copyArray has wrong array length");
        for (int i = 0; i < a.length; i++) {
            assertTrue(a[i] == a2[i],
                "copyArray: wrong array value: s/b " + a[i] + " is " + a2[i]);
        }
        a[0] = 99;
        assertTrue(a[0] != a2[0], "copyArray: array not copied");
    }

    /**
     * Test method: void bubbleSort(int[])
     */
    public static void testBubbleSort() {
        System.out.println("Testing bubbleSort");
        int[] a = {22, 12, 17, 29, 5, 67, 99, 0, 87, 43};
        ArrayUtil.bubbleSort(a);
        assertTrue(a.length == 10, "bubbleSort has wrong array length");
        for (int i = 0; i < a.length - 1; i++) {
            assertTrue(a[i] < a[i + 1],
                "bubbleSort array not sorted: " + a[i] + "," + a[i + 1]);
        }
        assertTrue(ArrayUtil.getComparisons() <= 90,
            "bubbleSort has too many comparisons: "
            + ArrayUtil.getComparisons());
        assertTrue(ArrayUtil.getComparisons() >= 80,
            "bubbleSort counting comparisons incorrectly: "
            + ArrayUtil.getComparisons());
    }

    /**
     * Test method: void bubbleSortPlus(int[])
     */
    public static void testBubbleSortPlus() {
        System.out.println("Testing bubbleSortPlus");
        int[] a = {22, 12, 17, 29, 5, 67, 99, 0, 87, 43};
        ArrayUtil.bubbleSortPlus(a);
        assertTrue(a.length == 10, "bubbleSortPlus: wrong array length");
        for (int i = 0; i < a.length - 1; i++) {
            assertTrue(a[i] < a[i + 1],
                "bubbleSortPlus does not sort array: "
                + a[i] + "," + a[i + 1]);
        }
        assertTrue(ArrayUtil.getComparisons() <= 45,
            "bubbleSortPlus did not remove unncecessary passes");
        assertTrue(ArrayUtil.getComparisons() > 40,
            "bubbleSortPlus counting comparisons incorrectly");
        int[] b = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10};
        ArrayUtil.bubbleSortPlus(b);
        assertTrue(ArrayUtil.getComparisons() < 10,
            "bubbleSortPlus did not implement no-swap test");
    }

    /**
     * Test method: int linearSearch(int[], int)
     */
    public static void testLinearSearch() {
        System.out.println("Testing linearSearch");
        int[] a = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10};
        int index = ArrayUtil.linearSearch(a, 5);
        assertTrue(a.length == 10, "linearSearch has wrong array length");
        assertTrue(index == 4, "linearSearch found wrong element index");
        index = ArrayUtil.linearSearch(a, 99);
        assertTrue(index == -1, "linearSearch found non-existant element");
    }

    /**
     * Test method: int binarySearch(int[], int)
     */
    public static void testBinarySearch() {
        System.out.println("Testing binarySearch");
        int[] a = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10};
        int index = ArrayUtil.binarySearch(a, 5);
        assertTrue(a.length == 10, "binarySearch has wrong array length");
        assertTrue(index == 4, "binarySearch found wrong element index");
        index = ArrayUtil.linearSearch(a, 99);
        assertTrue(index == -1, "binarySearch found non-existant element");
    }

    /**
     * Test method: void showArray(int[])
     */
    public static void testShowArray() {
        System.out.println("Testing showArray");
        int[] a = {1, 2, 3, 4, 5};
        System.out.print("- loopy showArray numbers s/b 1 to 5: ");
        ArrayUtil.showArray(a);
        assertTrue(a.length == 5, "showArray: array length modified");
    }

    /**
     * Test method: void showArray(int[], int)
     */
    public static void testShowArray2() {
        System.out.println("Testing recursive showArray");
        int[] a = {1, 2, 3, 4, 5};
        System.out.print("- rec. showArray numbers s/b 1 to 5: ");
        ArrayUtil.showArray(a, 0);
        assertTrue(a.length == 5, "rec. showArray: array length modified");
    }

    /**
     * Test method: void runTests(int[])
     */
    public static void testRunTests() {
        System.out.println("Testing runTests");
        int[] a = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10};
        ArrayUtil.runTests(a);
        assertTrue(a.length == 10, "runTests: array length modified");
    }
}
