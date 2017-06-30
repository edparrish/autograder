#include <iomanip>
#include <cmath>
#include <iostream>

using namespace std;

int main() {
  cout << "\nWelcome to the Great Cake company!\n";
  char orderMore = 'y';
  while (orderMore){
  cout << "\nEnter the cake order code: ";
  string orderCode;
  cin >> orderCode;
  string numbahCakes = orderCode.substr(2);
  string numbahCakes2 = orderCode.substr(1);
  double cakePrice = 34.99;
  
  double totalPrice = numberConvert * cakePrice;

  if (orderCode == "BF" + numbahCakes) {
  cout << numbahCakes << " Black Forest cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "CC" + numbahCakes) {
  cout << numbahCakes << " Carrot cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "CM" + numbahCakes) {
  cout << numbahCakes << " Chocolate Mint cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "DF" + numbahCakes) {
  cout << numbahCakes << " Devil's Food cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "GC" + numbahCakes) {
  cout << numbahCakes << " German Chocolate cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "PC" + numbahCakes) {
  cout << numbahCakes << " Pumpkin Cheescakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "RC" + numbahCakes) {
  cout << numbahCakes << " Rum cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
} if (orderCode == "T" + numbahCakes2) {
  cout << numbahCakes2 << " Tiramisu cakes" << endl;
  cout << "For " << numbahCakes << " cakes, order total: " << totalPrice;
}

  cout << "\nOrder more? (y/n) ";
  char answer;
  cin >> answer;
  if (answer == 'y') {
  orderMore = answer == 'y';
  cout << orderMore;
  }
  else
  return 0;
}
  return 0;
}
