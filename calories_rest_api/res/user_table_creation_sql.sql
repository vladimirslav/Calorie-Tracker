
CREATE TABLE Users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role TINYINT,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(32),
    daily_calories INT(4),
    reg_date BIGINT,
    auth_token VARCHAR(32),
    auth_expiry_date BIGINT
)