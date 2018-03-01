/**
*  Jacob Landowski
*  3-1-18
*/

CREATE TABLE User
(
    id            INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(20)  NOT NULL,
    email         VARCHAR(50)  NOT NULL,
    password      VARCHAR(255) NOT NULL,
    PRIMARY KEY(id)

) ENGINE=InnoDB;

/*Don't delete Thread if User is deleted*/
CREATE TABLE Thread
(
    id            INT         NOT NULL AUTO_INCREMENT,
    owner         INT         NOT NULL,
    title         VARCHAR(40) NOT NULL,
    created       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bot_generated TINYINT     NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    FOREIGN KEY(owner)     REFERENCES User(id) 

) ENGINE=InnoDB;

/*Don't delete Post if User is deleted*/
CREATE TABLE Post
(
    id            INT       NOT NULL AUTO_INCREMENT,
    thread        INT       NOT NULL,
    owner         INT       NOT NULL,
    created       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    content       TEXT      NOT NULL DEFAULT '',
    bot_generated TINYINT   NOT NULL DEFAULT 0,
    is_root_post  TINYINT   NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    FOREIGN KEY(thread) REFERENCES Thread(id) ON DELETE CASCADE,
    FOREIGN KEY(owner)  REFERENCES User(id)

) ENGINE=InnoDB;

CREATE TABLE TextMap
(
    id       INT        NOT NULL AUTO_INCREMENT,
    map_data MEDIUMBLOB NOT NULL,
    owner    INT        NOT NULL,    
    PRIMARY KEY(id),
    FOREIGN KEY(owner) REFERENCES User(id) ON DELETE CASCADE

) ENGINE=InnoDB;