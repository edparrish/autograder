/**
    CS-11 Asn 0, hello.cpp
    Purpose: Prints a message to the screen.

    @author First Student
    @version 1.0 6/14/17
*/
#include <iostream>
using namespace std;

int main() {
    cout << "Enter your name: ";
    string name;
    cin >> name;
    cout << "Hello, " << name << "!\n";
    return 0;
} // end of main function
