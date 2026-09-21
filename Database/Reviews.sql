CREATE TABLE IF NOT EXISTS `Reviews` (
    `Review_id` INT NOT NULL AUTO_INCREMENT,
    `User_id` INT NOT NULL,
    `Rating` TINYINT NOT NULL,
    `Titel` VARCHAR(150) NOT NULL,
    `Omschrijving` TEXT NOT NULL,
    `Ervaring_datum` DATE NULL,
    `Aangemaakt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Gepubliceerd` TINYINT(1) NOT NULL DEFAULT 1,

    PRIMARY KEY (`Review_id`),
    INDEX `IDX_Reviews_User_id` (`User_id`),
    INDEX `IDX_Reviews_Gepubliceerd_Aangemaakt` (`Gepubliceerd`, `Aangemaakt`),

    CONSTRAINT `FK_Reviews_User`
        FOREIGN KEY (`User_id`)
        REFERENCES `User` (`User_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `CHK_Reviews_Rating`
        CHECK (`Rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
