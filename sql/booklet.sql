USE Pokemon;
CREATE TABLE booklet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pokemon_name VARCHAR(255) NOT NULL,
    rarity VARCHAR(50),
    type1 VARCHAR(50),
    type2 VARCHAR(50),
    image_url VARCHAR(255),
    background_image_url VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES account(user_id) ON DELETE CASCADE
);