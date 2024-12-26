USE Pokemon;
CREATE TABLE booklet (
    user_id INT NOT NULL,
    card_id INT NOT NULL,
    pokemon_name VARCHAR(255) NOT NULL,
    background_image_url VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES account(user_id) ON DELETE CASCADE
);