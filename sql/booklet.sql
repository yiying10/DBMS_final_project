USE Pokemon;

CREATE TABLE booklet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pokemon_name VARCHAR(50) NOT NULL,
    card_background VARCHAR(50) NOT NULL,
    favorite BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
