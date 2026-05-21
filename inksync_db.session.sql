CREATE TABLE users (
    id INT SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);   

select * from users;

select * from storyboard_panels;

DELETE FROM users;


\timing

EXPLAIN ANALYZE 
SELECT shot_number, prompt, image_url 
FROM storyboard_panels 
WHERE project_id = 101 
ORDER BY shot_number ASC;


EXPLAIN ANALYZE 
SELECT shot_number, prompt, image_url x
FROM storyboard_panels 
WHERE project_id = 102 
ORDER BY shot_number ASC;