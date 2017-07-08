/**
 * CS-11 Asn 8
 * Product.cpp
 * Purpose: represents a product in a store.
 *
 * @author Ed Parrish
 * @version 1.4 4/13/08
 */
#include <iostream>
#include <iomanip>
#include <sstream>
using namespace std;

class Product {
public:
    /**
        Default constructor.
    */
    Product();

    /**
        Constructs a Product.

        @param name Name of the product.
        @param price Price of the product.
        @param qty Quantity of the product in stock.
    */
    Product(string name, double price, int qty);

    /**
        Returns the name of this product.

        @return The name of this product.
    */
    string getName() const;

    /**
        Returns the price of this product.

        @return The price of this product.
    */
    double getPrice() const;

    /**
        Returns the quantity of this product in stock.

        @return The quantity of this product.
    */
    int getQuantity() const;

    /**
        Returns the dollar value of this product in stock.

        @return The dollar value of this product.
    */
    double getValue() const;

    /**
        Changes the name of this product.

        @param newName New name of this product.
    */
    void setName(string newName);

    /**
        Changes the price of this product.

        @param newPrice New price of this product.
    */
    void setPrice(double newPrice);

    /**
        Changes the quantity of this product.

        @param newQuantity New quantity of this product.
    */
    void setQuantity(int newQuantity);

    /**
        Displays information about this product to the screen.
    */
    void print() const;

    /**
        Returns information about this product as a string.
     */
    string toString();

private:
    // Instance variables
    string name;
    double price;
    int quantity;
};

// Default constructor
Product::Product() {
    price = 0.0;
    quantity = 0;
}

Product::Product(string name, double price, int qty) {
    setName(name);
    setPrice(price);
    setQuantity(qty);
}

string Product::getName() const {
    return name;
}

double Product::getPrice() const {
    return price;
}

int Product::getQuantity() const {
    return quantity;
}

double Product::getValue() const {
    return price * quantity;
}

void Product::setName(string newName) {
    name = newName;
}

void Product::setPrice(double newPrice) {
    if (newPrice > 0.0) {
        price = newPrice;
    } else {
        price = 0.0;
    }
}

void Product::setQuantity(int newQuantity) {
    if (newQuantity > 0) {
        quantity = newQuantity;
    } else {
        quantity = 0;
    }
}

void Product::print() const {
    cout.setf(ios::fixed);
    cout << setw(16) << left << name
         << setw(8) << setprecision(2) << right << price
         << setw(6) << setprecision(0) << quantity
         << setw(10) << setprecision(2) << getValue() << endl;
}

string Product::toString() {
    stringstream sstr;
    sstr.setf(ios::fixed);
    sstr << setw(16) << left << name
         << setw(8) << setprecision(2) << right << price
         << setw(6) << setprecision(0) << quantity
         << setw(10) << setprecision(2) << getValue() << endl;
    string output = sstr.str();
    return output;
}

// For testing
const double MILK_PRICE = 3.95;
const int MILK_QTY = 40;
const double BREAD_PRICE = 2.99;
const int BREAD_QTY = 30;
const double CHEESE_PRICE = 4.98;
const int CHEESE_QTY = 20;

// Tests operation of the Product class.
int main() {
    cout << "My products:\n";
    cout << setw(16) << left << "Name"
         << setw(8) << right << "Price"
         << setw(6) << "Qty"
         << setw(10) << "Value" << endl;
    Product milk("Milk", MILK_PRICE, MILK_QTY);
    Product bread("Bread", BREAD_PRICE, BREAD_QTY);
    Product cheese;
    cheese.setName("Cheese");
    cheese.setPrice(CHEESE_PRICE);
    cheese.setQuantity(CHEESE_QTY);

    milk.print();
    bread.print();
    cout << cheese.toString() << endl;

    return 0;
}
