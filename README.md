# School System Website

A role-based dashboard website built with PHP, HTML, CSS, and JavaScript.

## Files

- `index.php` — login page for users with assigned credentials
- `dashboard.php` — displays the correct dashboard based on role
- `logout.php` — logs the user out
- `users.php` — stores the sample usernames and passwords
- `auth.php` — authentication helper functions and session handling
- `about.php` — about page for the school system
- `contact.php` — contact/support page
- `styles.css` — responsive site styles
- `script.js` — menu toggle and page enhancements

## Available credentials

Use these credentials to open the correct dashboard:

- Head Master: `headmaster` / `head123`
- Second Master: `secondmaster` / `second123`
- Academic Master: `academicmaster` / `academic123`
- Head of Departments: `hod` / `hod123`
- Teacher: `teacher1` / `teach123`
- Student: `student1` / `study123`

## Run locally

1. Open a terminal in this folder.
2. Start the PHP built-in server:

```bash
php -S localhost:8000
```

3. Open `http://localhost:8000` in your browser.

## Notes

- This version uses PHP sessions to track logged-in users.
- Each login opens a dashboard tailored to the user's role.
- The system uses fixed credentials in `users.php`; later you can replace this with a database.
