CREATE DATABASE gym_database;

USE gym_database;



CREATE TABLE Gym (
    Gym_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Address TEXT NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Contact VARCHAR(15) NOT NULL
);



CREATE TABLE Trainer (
    Trainer_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Contact VARCHAR(15) NOT NULL,
    Gym_ID INT NOT NULL,
    FOREIGN KEY (Gym_ID) REFERENCES Gym(Gym_ID)
);

CREATE TABLE Payment (
    Payment_ID INT AUTO_INCREMENT PRIMARY KEY,
    Amount DECIMAL(10, 2) NOT NULL,
    Date DATE NOT NULL,
    Gymgoer_ID INT NOT NULL,
    FOREIGN KEY (Gymgoer_ID) REFERENCES Gymgoer(Gymgoer_ID)
);

CREATE TABLE Mealplan (
    Mealplan_ID INT AUTO_INCREMENT PRIMARY KEY,
    Description TEXT NOT NULL,
    Trainer_ID INT NOT NULL,
    FOREIGN KEY (Trainer_ID) REFERENCES Trainer(Trainer_ID)
);

CREATE TABLE Workout (
    Workout_ID INT AUTO_INCREMENT PRIMARY KEY,
    Description TEXT NOT NULL,
    Trainer_ID INT NOT NULL,
    FOREIGN KEY (Trainer_ID) REFERENCES Trainer(Trainer_ID)
);

CREATE TABLE Seller (
    Seller_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Contact VARCHAR(15) NOT NULL
);

CREATE TABLE Category (
    Category_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE InventoryItem (
    InventoryItem_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    Category_ID INT NOT NULL,
    Seller_ID INT NOT NULL,
    FOREIGN KEY (Category_ID) REFERENCES Category(Category_ID),
    FOREIGN KEY (Seller_ID) REFERENCES Seller(Seller_ID)
);

CREATE TABLE Order (
    Order_ID INT AUTO_INCREMENT PRIMARY KEY,
    Price DECIMAL(10, 2) NOT NULL,
    Quantity INT NOT NULL,
    Gymgoer_ID INT NOT NULL,
    Seller_ID INT NOT NULL,
    FOREIGN KEY (Gymgoer_ID) REFERENCES Gymgoer(Gymgoer_ID),
    FOREIGN KEY (Seller_ID) REFERENCES Seller(Seller_ID)
);

CREATE TABLE OrderItem (
    OrderItem_ID INT AUTO_INCREMENT PRIMARY KEY,
    Price DECIMAL(10, 2) NOT NULL,
    Quantity INT NOT NULL,
    Order_ID INT NOT NULL,
    InventoryItem_ID INT NOT NULL,
    FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID),
    FOREIGN KEY (InventoryItem_ID) REFERENCES InventoryItem(InventoryItem_ID)
);

CREATE TABLE Cash (
    Cash_ID INT AUTO_INCREMENT PRIMARY KEY,
    Payment_ID INT NOT NULL,
    FOREIGN KEY (Payment_ID) REFERENCES Payment(Payment_ID)
);

CREATE TABLE Card (
    Card_ID INT AUTO_INCREMENT PRIMARY KEY,
    Card_No VARCHAR(16) NOT NULL UNIQUE,
    Security_No VARCHAR(3) NOT NULL,
    Payment_ID INT NOT NULL,
    FOREIGN KEY (Payment_ID) REFERENCES Payment(Payment_ID)
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('gym-goer', 'trainer', 'seller') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Gymgoer (
    Gymgoer_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Contact VARCHAR(15) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Gym_ID INT NOT NULL,
    Trainer_ID INT,
    Mealplan_ID INT,
    Workout_ID INT,
    FOREIGN KEY (Gym_ID) REFERENCES Gym(Gym_ID),
    FOREIGN KEY (Trainer_ID) REFERENCES Trainer(Trainer_ID),
    FOREIGN KEY (Mealplan_ID) REFERENCES Mealplan(Mealplan_ID),
    FOREIGN KEY (Workout_ID) REFERENCES Workout(Workout_ID)
);