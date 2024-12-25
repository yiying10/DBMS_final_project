USE Pokemon;
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    user_name VARCHAR(255),
    content TEXT,
    created_at DATETIME,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id)
);