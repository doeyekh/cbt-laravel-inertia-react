<?php
// Step 1: Create new table with proper schema
$db = new SQLite3("database/database.sqlite");
$db->exec("CREATE TABLE exam_schedules_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    exam_package_id INTEGER, 
    class_id INTEGER, 
    subject_id INTEGER, 
    session_id INTEGER, 
    room_id INTEGER, 
    exam_date DATE, 
    duration_minutes INTEGER, 
    open_time TIME, 
    close_time TIME, 
    shuffle_questions BOOLEAN, 
    shuffle_options BOOLEAN, 
    show_score_to_student BOOLEAN, 
    status TEXT, 
    created_at DATETIME, 
    updated_at DATETIME
)");

// Step 2: Copy data
$db->exec("INSERT INTO exam_schedules_new SELECT * FROM exam_schedules");

// Step 3: Delete old table
$db->exec("DROP TABLE exam_schedules");

// Step 4: Rename new table
$db->exec("ALTER TABLE exam_schedules_new RENAME TO exam_schedules");

// Verify
$result = $db->query("PRAGMA table_info(exam_schedules)");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "Column: " . $row["name"] . " Type: " . $row["type"] . " NotNull: " . $row["notnull"] . "\n";
}