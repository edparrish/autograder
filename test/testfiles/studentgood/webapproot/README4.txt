Name: Ed Parrish
Asn#: 4
Hours: 7.0
Status: Completed
Files:
- artzy.sql: Artzy database dump
- relationships.txt: Excercise 4.1
- doctors.txt: Excercise 4.2
- patterns.txt: Excercise 4.3
- query1.txt: first query
- query2.txt: second query
- query3.txt: third query
- query4.txt: extra credit query
- README.txt: Database design and SQL statements for A4.
Extra Credit: Extra credit query.

A. Project Description
Artzy Art Supplies is an online store for buying art supplies.

B. Tables, Columns and Keys
addresses(_ID_, Address, City, State, Zip, Country)
categories(_ID_, CategoryName, *ParentID*)
customers(_ID_, LName, FName, AddressID, Phone, Email)
orders(_ID_, *CustomerID*, OrderDate, OrderStatus, ProductTotal,
    ShippingTotal, TaxTotal, ShipName, AddressID)
orderitems(_*OrderID*_, _ItemNbr_, *ProductID*, PriceEach, Quantity)
products(_ID_, Name, Description, Path2Image, *SupplierID*,
    PriceEach, InStock, Weight)
productcategory(_*ProductID*_, _*CategoryID*_)
purchorders(_ID_, *ProductID*, Qty, Cost, DateOrdered)
shoppingcarts(_SessionID_, _*ProductID*_, AddDate, PriceCharged, Qty)
suppliers(_ID_, SupplierName, SupplierCode)
users(_UserName_, *CustomerID*, Salt, Userpwd, Email)
