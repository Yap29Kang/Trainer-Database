CREATE DATABASE training_management;
USE training_management;

-- =========================
-- TRAINING PROVIDER TABLE
-- =========================
CREATE TABLE TrainingProvider (
    TP_ID INT AUTO_INCREMENT PRIMARY KEY,
    TP_Name VARCHAR(255) NOT NULL,
    TP_Venue VARCHAR(255),
    TP_Status VARCHAR(100)
);

-- =========================
-- TRAINER TABLE
-- =========================
CREATE TABLE Trainer (
    Trainer_ID INT AUTO_INCREMENT PRIMARY KEY,
    Trainer_Name VARCHAR(255) NOT NULL,
    Trainer_Status VARCHAR(100)
);

-- =========================
-- ASSIGNMENT TABLE
-- MANY-TO-MANY:
-- TRAINER <-> TRAINING PROVIDER
-- =========================
CREATE TABLE Assignment (
    TP_ID INT,
    Trainer_ID INT,

    PRIMARY KEY (TP_ID, Trainer_ID),

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- ITEM / COURSE TABLE
-- =========================
CREATE TABLE Item (
    Item_ID INT AUTO_INCREMENT PRIMARY KEY,
    TP_ID INT NOT NULL,
    Trainer_ID INT NOT NULL,

    Item_Name VARCHAR(255) NOT NULL,
    Item_Category VARCHAR(255),
    Item_Date DATE,

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =========================
-- PARTICIPANT TABLE
-- =========================
CREATE TABLE Participant (
    Participant_ID INT AUTO_INCREMENT PRIMARY KEY,
    Participant_Name VARCHAR(255) NOT NULL
);

-- =========================
-- ENROLLMENT TABLE
-- MANY-TO-MANY:
-- PARTICIPANT <-> ITEM
-- =========================
CREATE TABLE Enrollment (
    Item_ID INT,
    Participant_ID INT,

    Completion_Date DATE

    PRIMARY KEY (Item_ID, Participant_ID),

    FOREIGN KEY (Item_ID)
        REFERENCES Item(Item_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Participant_ID)
        REFERENCES Participant(Participant_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);