
CREATE TABLE Meals (
    id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner INT(6) UNSIGNED NOT NULL,
    text VARCHAR(255),
    meal_date BIGINT NOT NULL,
    meal_time INT(4) UNSIGNED NOT NULL,
    calories SMALLINT NOT NULL,
    CONSTRAINT fk_MealUsers FOREIGN KEY (owner)
    REFERENCES Users(id)
)