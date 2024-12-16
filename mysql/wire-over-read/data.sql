USE php_test;

-- Drop the table if it exists
DROP TABLE IF EXISTS users;

-- Create the 'users' table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert test data
INSERT INTO users (username, email) 
VALUES
    ('user1', 'user1@example.com'),
    ('user2', 'user2@example.com'),
    ('user3', 'user3@example.com'),
    ('user4', 'user4@example.com'),
    ('user5', 'user5@example.com');


-- Drop the table if it exists
DROP TABLE IF EXISTS items;

-- Create the 'items' table
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item VARCHAR(50) NOT NULL
);

-- Insert test data
INSERT INTO items (item) VALUES ('test');

-- Drop the table if it exists
DROP TABLE IF EXISTS data;

-- Create the 'data' table
CREATE TABLE data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    strval VARCHAR(50) NOT NULL,
    datval DATE NOT NULL,
    dtival DATETIME NOT NULL,
    timval TIME NOT NULL,
    dblval DOUBLE NOT NULL,
    fltval FLOAT NOT NULL,
    intval INT NOT NULL,
    bitval BIT(64) NOT NULL
);

-- Insert test data
INSERT INTO data (strval, datval, dtival, timval, dblval, fltval, intval, bitval)
VALUES ('test', '2014-12-15', '2014-12-16 13:00:01', '13:00:02', 1.2, 2.3, 14, b'00001111000011110000111100001111');