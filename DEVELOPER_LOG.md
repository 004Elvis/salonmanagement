Purpose: Technical explanation of the code logic for future maintenance.

# Developer's Log: Technical Implementations

## 1. Authentication Logic
The system uses session-based authentication. The `checkRole()` function ensures that users cannot access directories outside their permissions.


## 2. Performance Tracking
Staff performance is visualized using a dynamic CSS bar graph.
**Logic:**
The height of each bar is calculated in PHP by dividing the daily appointment count by the maximum count found in the current week.

## 3. Secure File Uploads
To prevent directory traversal and malicious file execution:
1. File extensions are whitelisted (`jpg`, `png`, `webp`).
2. Files are uniquely renamed using `time()` and `user_id`.


## 4. Database Safety
All queries utilize **PDO Prepared Statements**. This separates the query command from the user-provided data, effectively neutralizing SQL Injection risks.