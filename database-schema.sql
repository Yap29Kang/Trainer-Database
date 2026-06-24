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
    Trainer_Name VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS TrainerStatus (
    Trainer_Status_ID SERIAL PRIMARY KEY,
    Trainer_ID INT NOT NULL,

    Trainer_Status VARCHAR(100) NOT NULL DEFAULT 'Active',
    Trainer_StatusReasoning TEXT,
    Trainer_StatusStartDate DATE,
    Trainer_StatusEndDate DATE,

    FOREIGN KEY (Trainer_ID)
        REFERENCES Trainer(Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_trainerstatus_trainer_id_startdate
    ON TrainerStatus (Trainer_ID, Trainer_StatusStartDate DESC, Trainer_Status_ID DESC);

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

CREATE TABLE IF NOT EXISTS Participant (
    Participant_ID SERIAL PRIMARY KEY,
    Participant_Token VARCHAR(64) NOT NULL UNIQUE,
    Participant_Name_Hash CHAR(64) NOT NULL UNIQUE,
    Participant_Name_Encrypted TEXT NOT NULL,
    Participant_Department VARCHAR(255)
);

-- Upload must be defined before Item since Item.Upload_ID references it
CREATE TABLE IF NOT EXISTS Upload (
    Upload_ID SERIAL PRIMARY KEY,
    Filename VARCHAR(255) NOT NULL,
    Upload_Date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Upload_Status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (Upload_Status IN ('active', 'removed')),
    Record_Count INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Item (
    Item_ID SERIAL PRIMARY KEY,

    TP_ID INT NOT NULL,
    Trainer_ID INT NOT NULL,
    Upload_ID INT NULL,

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

    -- CASCADE: if an Upload row is ever hard-deleted, its Items are deleted too
    -- NOT NULL + CASCADE is consistent; SET NULL would conflict with NOT NULL
    FOREIGN KEY (Upload_ID)
        REFERENCES Upload(Upload_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Ensures Trainer is assigned to that Provider
    CONSTRAINT fk_assignment_validation
        FOREIGN KEY (TP_ID, Trainer_ID)
        REFERENCES Assignment(TP_ID, Trainer_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_item_upload_id ON Item (Upload_ID);
CREATE INDEX IF NOT EXISTS idx_item_tp_id ON Item (TP_ID);
CREATE INDEX IF NOT EXISTS idx_item_trainer_id ON Item (Trainer_ID);
CREATE INDEX IF NOT EXISTS idx_item_tp_trainer ON Item (TP_ID, Trainer_ID);
CREATE INDEX IF NOT EXISTS idx_item_category ON Item (Item_Category);

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

-- COMPLAINT TABLE
CREATE TABLE IF NOT EXISTS Complaint (
    case_id TEXT PRIMARY KEY,
    date_of_complaint DATE NOT NULL,
    employee_name TEXT NOT NULL,
    employee_id TEXT NOT NULL,
    department VARCHAR(255),
    learnops TEXT NOT NULL,
    training_provider_id INT NOT NULL,
    complaint_category TEXT NOT NULL CHECK (complaint_category IN ('Performance Quality', 'Safety & Compliance', 'Fraud & Misconduct')),
    complaint_summary TEXT NOT NULL,
    priority TEXT NOT NULL CHECK (priority IN ('Low', 'Medium', 'High')),
    status TEXT NOT NULL CHECK (status IN ('Open', 'Under Review', 'Closed')),
    ldcm_decision TEXT CHECK (ldcm_decision IN ('No Action', 'LDCM Decision', 'Blacklist')),
    decision_date DATE,
    remarks TEXT,
    
    FOREIGN KEY (training_provider_id)
        REFERENCES TrainingProvider(TP_ID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE OR REPLACE FUNCTION set_complaint_case_id()
RETURNS TRIGGER AS $$
DECLARE
    current_yr TEXT;
    next_val INT;
BEGIN
    current_yr := to_char(CURRENT_DATE, 'YYYY');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(case_id FROM 11 FOR 3) AS INT)), 0) + 1
    INTO next_val
    FROM Complaint
    WHERE SUBSTRING(case_id FROM 6 FOR 4) = current_yr;
    
    NEW.case_id := 'LDCM-' || current_yr || '-' || LPAD(next_val::TEXT, 3, '0');
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_set_complaint_case_id ON Complaint;
CREATE TRIGGER trigger_set_complaint_case_id
BEFORE INSERT ON Complaint
FOR EACH ROW
WHEN (NEW.case_id IS NULL)
EXECUTE FUNCTION set_complaint_case_id();

CREATE TABLE IF NOT EXISTS complaint_audit_log (
    audit_id        SERIAL PRIMARY KEY,
    case_id         VARCHAR(50)  NOT NULL REFERENCES Complaint(case_id) ON DELETE CASCADE,
    changed_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changed_by      VARCHAR(150),          -- session username / name of the admin who saved
    status          VARCHAR(50),
    ldcm_decision   VARCHAR(100),
    decision_date   DATE,
    remarks         TEXT
);
 
CREATE INDEX IF NOT EXISTS idx_complaint_audit_case_id
    ON complaint_audit_log (case_id, changed_at DESC);