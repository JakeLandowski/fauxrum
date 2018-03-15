/**
*  Jacob Landowski
*  3-1-18
*/

CREATE TABLE User
(
    id            INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(20),
    email         VARCHAR(50),
    password      VARCHAR(255) NOT NULL,
    PRIMARY KEY(id),
    UNIQUE(username),
    UNIQUE(email)

) ENGINE=InnoDB;

/* CHECK FOR THIS USER ON LOGINS */
INSERT INTO User(username, email, password) VALUES('USER_GRAVEYARD', 'unused', 'unused');

/* CREATE THREAD GRAVEYARD WHEN USER IS MADE */
CREATE TABLE Thread
(
    id            INT         NOT NULL AUTO_INCREMENT,
    owner         INT         NOT NULL,
    title         VARCHAR(40),
    replies       INT         NOT NULL DEFAULT 0,
    views         INT         NOT NULL DEFAULT 0,
    created       TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bot_generated TINYINT     NOT NULL DEFAULT 0,
    parsed        TINYINT     NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    FOREIGN KEY(owner) REFERENCES User(id), 
    UNIQUE(title)

) ENGINE=InnoDB;

CREATE TABLE Thread_User_Views
(
    id     INT NOT NULL AUTO_INCREMENT,
    thread INT NOT NULL,
    user   INT NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY(thread) REFERENCES Thread(id) ON DELETE CASCADE,
    FOREIGN KEY(user)   REFERENCES User(id)   ON DELETE CASCADE,
    UNIQUE(thread, user)
) ENGINE=InnoDB;

CREATE TABLE Post
(
    id            INT       NOT NULL AUTO_INCREMENT,
    thread        INT       NOT NULL,
    owner         INT       NOT NULL,
    created       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    content       TEXT      NOT NULL,
    bot_generated TINYINT   NOT NULL DEFAULT 0,
    parsed        TINYINT   NOT NULL DEFAULT 0,
    is_root_post  TINYINT   NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    FOREIGN KEY(thread) REFERENCES Thread(id),
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