USE Pokemon;

CREATE TABLE account (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(50) NOT NULL,
    account VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);
