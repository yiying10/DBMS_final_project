USE Pokemon;

CREATE TABLE natures (
    Nature VARCHAR(15) NOT NULL PRIMARY KEY,
    Increases VARCHAR(10),
    Decreases VARCHAR(10),
    Likes_berrie VARCHAR(10),
    Dislikes_berrie VARCHAR(10)
);