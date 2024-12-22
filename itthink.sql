CREATE DATABASE ITThink;

USE ITThink;

-- Create utilisateurs Table
CREATE TABLE utilisateurs (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL
    role VARCHAR(150) UNIQUE NOT NULL
);

-- Create Categories Table
CREATE TABLE Categories (
    id_category INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL
);

-- Create Subcategories Table
CREATE TABLE Subcategories (
    id_subcategory INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_name VARCHAR(100) NOT NULL,
    id_category INT NOT NULL,
    FOREIGN KEY (id_category) REFERENCES Categories(id_category) ON DELETE CASCADE
);

-- Create Projects Table
CREATE TABLE Projects (
    id_project INT AUTO_INCREMENT PRIMARY KEY,
    project_title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    id_category INT NOT NULL,
    id_subcategory INT NOT NULL,
    id_user INT NOT NULL,
    FOREIGN KEY (id_category) REFERENCES Categories(id_category) ON DELETE CASCADE,
    FOREIGN KEY (id_subcategory) REFERENCES Subcategories(id_subcategory) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES Users(id_user) ON DELETE CASCADE
);

-- Create Freelancers Table
CREATE TABLE Freelancers (
    id_freelancer INT AUTO_INCREMENT PRIMARY KEY,
    freelancer_name VARCHAR(100) NOT NULL,
    skills TEXT NOT NULL,
    id_user INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES Users(id_user) ON DELETE CASCADE
);

-- Create Offers Table
CREATE TABLE Offers (
    id_offer INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10, 2) NOT NULL,
    deadline DATE NOT NULL,
    id_freelancer INT NOT NULL,
    id_project INT NOT NULL,
    FOREIGN KEY (id_freelancer) REFERENCES Freelancers(id_freelancer) ON DELETE CASCADE,
    FOREIGN KEY (id_project) REFERENCES Projects(id_project) ON DELETE CASCADE
);

-- Create Testimonials Table
CREATE TABLE Testimonials (
    id_testimonial INT AUTO_INCREMENT PRIMARY KEY,
    comment TEXT NOT NULL,
    id_user INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES Users(id_user) ON DELETE CASCADE
);


-- edit 

ALTER TABLE Projects
ADD CurrentDate DATE;


-- ajoute

INSERT INTO Categories (category_name)
VALUES ('Web Development');

INSERT INTO Subcategories (subcategory_name, id_categorie)
VALUES ('Frontend Development', 1);

INSERT INTO Projects (project_title, description, id_category, id_subcategory, id_user, date_creation)
VALUES ('Build a Portfolio Website', 'A project to create a personal portfolio website.', 1, 1, 1, CURDATE());

-- modifier

UPDATE Projects
SET date_creation = '2024-10-10'
WHERE id_project = 1;

RENAME TABLE Users TO Utilisateurs,
testimonial TO Temoignage;

INSERT INTO Temoignage (comment, id_user)
VALUES 
    ('This platform is amazing!', 1),
    ('Great experience working on projects here.', 1),
    ('The best freelance website I have used.', 31;


---- delete

DELETE FROM temoignage
WHERE id_testimonial = 2;


----- jointure 

SELECT * FROM Projects
INNER JOIN Categories ON Projects.id_category = Categories.id_category
WHERE Categories.category_name = 'Web Development';


