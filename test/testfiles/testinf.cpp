/**
  See if testing will survive an infinite loop in C++.
*/
#include <iostream>
using namespace std;

int main() {
    cout << "Starting an infinite loop...\n";
    int x = 0;
    while (true) {
        x = x + 1;
        if (x % 10000 == 0) {
            cout << "Still going x=" << x << endl;
            cerr << "Standard error x=" << x << endl;
        }
    }
}
