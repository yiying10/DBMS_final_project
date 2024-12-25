USE Pokemon;
CREATE TABLE booklet (
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    pokemon_name VARCHAR(255) NOT NULL,
   Rarity VARCHAR(50) DEFAULT NULL,  
    Type1 VARCHAR(50) NOT NULL,                 
    Type2 VARCHAR(50) DEFAULT NULL,  
    image_url VARCHAR(255),
    background_image_url VARCHAR(255),
    Ability VARCHAR(30),
    Description TEXT,
    FOREIGN KEY (user_id) REFERENCES account(user_id) ON DELETE CASCADE
);