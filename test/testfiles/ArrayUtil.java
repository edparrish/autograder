import java.util.Scanner;

/**
 * CIS20  Asg4
 * ArrayUtil.java
 * Purpose: Utilities to use on arrays.
 *
 * @version 1.1 3/13/05
 * @author Ed Parrish
 */
public class ArrayUtil {
    public static final int LOW = 0;
    public static final int HIGH = 100;
    private static int comparisons = 0;

    /**
     * The main method begins execution of Java application.
     *
     * @param args not used
     */
    public static void main(String[] args) {
        Scanner input  = new Scanner(System.in);
        boolean valid = false;
        int size = 0;

        System.out.println(
            "\nThis program requests an array size from the user,\n"
            + "creates an array of that size filled with random numbers,\n"
            + "and tests the array.\n");

        while (!valid) {
            System.out.print("Enter the array size to test (0 to exit): ");
            size = input.nextInt();
            if (size == 0) {
                System.exit(0);
            } else if (size > 0) {
                valid = true;
            } else {
                System.out.println("Size cannot be a negative number!");
            }
        }
        int[] array = makeArray(size);
        runTests(array);
    }

    /**
     * Accessor method for comparisons.
     *
     * @return Current count of comparisons.
     */
    public static int getComparisons() {
        return comparisons;
    }

    /**
     * Runs tests on the int array.
     *
     * @param array The array to test.
     */
    public static void runTests(int[] array) {
        System.out.println("Original array elements are:");
        showArray(array);
        int[] arrayCopy = copyArray(array);
        bubbleSort(arrayCopy);
        System.out.println("\nBubble Sort comparisons needed: "
            + comparisons);
        System.out.println("Sorted array elements are:");
        showArray(arrayCopy);
        arrayCopy = copyArray(array);
        bubbleSortPlus(arrayCopy);
        System.out.println("Enhanced Bubble Sort comparisons needed: "
            + comparisons);
        showArray(arrayCopy, 0);
        int item = (int) (Math.random() * (array.length - 1));
        System.out.println("\nSearching for: " + array[item]);
        linearSearch(arrayCopy, array[item]);
        System.out.println("Linear search Comparisons needed: "
            + comparisons);
        binarySearch(arrayCopy, array[item]);
        System.out.println("Binary search Comparisons needed: "
            + comparisons);
    }

    /**
     * Creates an array of size and initializes the array with random
     * values between LOW and HIGH - 1.
     *
     * @param size The number of elements to create in the array.
     *
     * @return An array initialized with random values between LOW and HIGH - 1.
     */
    public static int[] makeArray(int size) {
        int[] array = new int[size];
        for (int i = 0; i < array.length; i++) {
            array[i] = (int) (Math.random() * (HIGH - LOW) + LOW);
        }
        return array;
    }

    /**
     * Creates an copy of an array and returns the copy.
     *
     * @param array The original array to copy from.
     *
     * @return An new array initialized with the elements of the original array.
     */
    public static int[] copyArray(int[] array) {
        int[] newArray = new int[array.length];
        for (int i = 0; i < array.length; i++)
            newArray[i] = array[i];
        return newArray;
    }

    /**
     * Sorts an array using bubble sort.
     *
     * @param array The array to sort.
     */
    public static void bubbleSort(int[] array) {
        comparisons = 0;
        for (int i = 0; i < array.length; i++) {
            for (int j = 0; j < array.length - 1; j++) {
                comparisons++;
                if (array[j] > array[j + 1]) swap(array, j, j + 1);
            }
        }
    }

    /**
     * Sorts an array using an enhanced bubble sort.
     *
     * @param array The array to sort.
     */
    public static void bubbleSortPlus(int[] array) {
        boolean didSwap; // boolean indicating if a swap took place during pass
        comparisons = 0;
        for (int pass = 1; pass < array.length; pass++) {
            didSwap = false;
            for (int element = 0; element < array.length - pass; element++) {
                comparisons++;
                if (array[element] > array[element + 1]) {
                    swap(array, element, element + 1);
                    didSwap = true;
                }
            }
            // if no swaps, terminate bubble sort
            if (!didSwap)
                return;
        }
    }

    /**
     * Swap two elements in an array
     *
     * @param array -- array of integers being sorted
     * @param first -- first position
     * @param second -- second position
     */
    static void swap(int array[], int first, int second) {
        int temp = array[first];
        array[first] = array[second];
        array[second] = temp;
    }

    /**
     * Searches an array for a specified key value using linear search.
     *
     * @param array The array to search.
     * @param key The value for which to search.
     *
     * @return The index of the array element containing the key value,
     *      or -1 if the key was not found.
     */
    public static int linearSearch(int[] array, int key) {
        comparisons = 0;
        for (int counter = 0; counter < array.length; counter++) {
            comparisons++;
            // if array element equals key value, return location
            if (array[counter] == key)
                return counter;
        }
        return -1;  // key not found
    }

    /**
     * Searches an array for a specified key value using binary search.
     *
     * @param array The array to search.
     * @param key The value for which to search.
     *
     * @return The index of the array element containing the key value,
     *      or -1 if the key was not found.
     */
    public static int binarySearch(int[] array, int key) {
        int start = 0;                // start element subscript
        int end = array.length - 1;   // end element subscript
        int middle;                   // middle element subscript
        comparisons = 0;

        while (start <= end) {
            comparisons++;
            // determine middle element subscript
            middle = (start + end) / 2;
            // if key matches middle element, return middle location
            if (key == array[middle]) {
                return middle;
            // if key less than middle element, set new end element
            } else if (key < array[middle]) {
                end = middle - 1;
            // key greater than middle element, set new start element
            } else {
                start = middle + 1;
            }
        }
        return -1;   // key not found
    }

    /**
     * Iteratively prints an array from start to the end.
     *
     * @param array The array to print.
     */
    public static void showArray(int[] array) {
        for (int i = 0; i < array.length - 1; i++) {
            System.out.print(array[i] + ", ");
        }
        if (array.length > 0)
            System.out.println(array[array.length - 1]);
    }

    /**
     * Recursively prints an array from start to the end.
     *
     * @param array The array to print.
     * @param start The starting position in the array to print.
     */
    public static void showArray(int[] array, int start) {
        if (start >= array.length - 1) {
            if (array.length > 0)
                System.out.println(array[array.length - 1]);
        } else {
            System.out.print(array[start] + ", ");
            showArray(array, ++start);
        }
    }
}
