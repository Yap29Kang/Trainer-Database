-- Postgres schema for Trainer Database

-- TRAINING PROVIDER TABLE
CREATE TABLE IF NOT EXISTS TrainingProvider (
    TP_ID SERIAL PRIMARY KEY,
    TP_Name VARCHAR(255) NOT NULL,

    -- Areas of Expertise
    TP_FirstAoE VARCHAR(255),
    TP_SecondAoE VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS TrainingProviderStatus (
    TP_Status_ID SERIAL PRIMARY KEY,
    TP_ID INT NOT NULL,

    TP_Status VARCHAR(100) NOT NULL DEFAULT 'Active',
    TP_StatusReasoning TEXT,
    TP_StatusStartDate DATE,
    TP_StatusEndDate DATE,

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_trainingproviderstatus_tp_id_startdate
    ON TrainingProviderStatus (TP_ID, TP_StatusStartDate DESC, TP_Status_ID DESC);

CREATE TABLE IF NOT EXISTS TrainingProviderRemark (
    TP_Remark_ID SERIAL PRIMARY KEY,
    TP_ID INT NOT NULL,

    Remark_Text TEXT,
    Remark_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_trainingproviderremark_tp_id_date
    ON TrainingProviderRemark (TP_ID, Remark_Date DESC, TP_Remark_ID DESC);

CREATE TABLE IF NOT EXISTS Trainer (
    Trainer_ID SERIAL PRIMARY KEY,
    Trainer_Name VARCHAR(255) NOT NULL,
    Trainer_Status VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS TrainerRemark (
    Trainer_Remark_ID SERIAL PRIMARY KEY,
    Trainer_ID INT NOT NULL,

    Remark_Text TEXT,
    Remark_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_trainerremark_trainer_id_date
    ON TrainerRemark (Trainer_ID, Remark_Date DESC, Trainer_Remark_ID DESC);

CREATE TABLE IF NOT EXISTS Assignment (
    Assignment_ID SERIAL PRIMARY KEY,
    TP_ID INT,
    Trainer_ID INT,
    -- keep uniqueness on pair to preserve assignment semantics
    UNIQUE (TP_ID, Trainer_ID),

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

    CREATE INDEX IF NOT EXISTS idx_assignment_tp_id ON Assignment (TP_ID);
    CREATE INDEX IF NOT EXISTS idx_assignment_trainer_id ON Assignment (Trainer_ID);

CREATE TABLE IF NOT EXISTS Item (
    Item_ID SERIAL PRIMARY KEY,

    TP_ID INT NOT NULL,
    Trainer_ID INT NOT NULL,

    Item_Name VARCHAR(255) NOT NULL,
    Item_Venue VARCHAR(255),
    Item_Category VARCHAR(255),

    FOREIGN KEY (TP_ID)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Ensures Trainer is assigned to that Provider
    CONSTRAINT fk_assignment_validation
        FOREIGN KEY (TP_ID, Trainer_ID)
        REFERENCES Assignment(TP_ID, Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_item_tp_id ON Item (TP_ID);
CREATE INDEX IF NOT EXISTS idx_item_trainer_id ON Item (Trainer_ID);
CREATE INDEX IF NOT EXISTS idx_item_tp_trainer ON Item (TP_ID, Trainer_ID);
CREATE INDEX IF NOT EXISTS idx_item_category ON Item (Item_Category);

CREATE TABLE IF NOT EXISTS Participant (
    Participant_ID SERIAL PRIMARY KEY,
    Participant_Token VARCHAR(64) NOT NULL UNIQUE,
    Participant_Name_Hash CHAR(64) NOT NULL UNIQUE,
    Participant_Name_Encrypted TEXT NOT NULL,
    Participant_Department VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS Enrollment (
    Enrollment_ID SERIAL PRIMARY KEY,
    Item_ID INT,
    Participant_ID INT,
    Completion_Date DATE,

    -- preserve previous uniqueness constraint so canonical enrollments remain unique
    UNIQUE (Item_ID, Participant_ID),

    FOREIGN KEY (Item_ID)
        REFERENCES Item(Item_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (Participant_ID)
        REFERENCES Participant(Participant_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_enrollment_item_id ON Enrollment (Item_ID);
CREATE INDEX IF NOT EXISTS idx_enrollment_participant_id ON Enrollment (Participant_ID);