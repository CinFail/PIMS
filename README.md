PAKIBASA


Step 1: Install Git (if not already installed)
Check if Git is installed:
bashgit --version
If no version appears, download and install from git-scm.com

Step 2: Choose a folder location
Pick where you want the project. For example:
C:\projects\           (Windows)
~/projects/            (macOS/Linux)
Open terminal/command prompt in that folder.

Step 3: Clone your GitHub repository
Replace YOUR-USERNAME with your actual GitHub username:
bashgit clone https://github.com/YOUR-USERNAME/pims
This downloads your entire project. Wait for it to finish (~1-2 min).

Step 4: Go into the project folder
bashcd pims

Step 5: Install PHP packages
bashcomposer install
This downloads Laravel and all dependencies. Takes a few minutes. Ignore any warnings.

Step 6: Create the environment file
Copy .env.example to .env:
Windows (PowerShell):
bashcopy .env.example .env
macOS/Linux:
bashcp .env.example .env

Step 7: Generate the application key
bashphp artisan key:generate
You should see: Application key set successfully.

Step 8: Create the MySQL database
Open MySQL (via terminal, phpMyAdmin, MySQL Workbench, or any GUI):
sqlCREATE DATABASE pims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
That's it. Leave it empty for now.

Step 9: Import the SQL files (IMPORTANT: order matters)
The SQL files are in your project at database/sql/
Import FIRST — the schema (tables):
bashmysql -u root -p pims_db < database/sql/PIMS_DB_corrected.sql
It will ask for your MySQL password (press Enter if there's no password).
Import SECOND — the demo data:
bashmysql -u root -p pims_db < database/sql/PIMS_seed.sql

Step 10: Configure the .env file
Open the .env file in your code editor and find these lines:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pims_db
DB_USERNAME=root
DB_PASSWORD=
Update them:

DB_HOST → if MySQL is local, leave as 127.0.0.1
DB_DATABASE → keep as pims_db (the database you created)
DB_USERNAME → your MySQL username (usually root)
DB_PASSWORD → your MySQL password (leave blank if no password)

Example with password:
DB_PASSWORD=mypassword123
Save the file.

Step 11: Link the storage folder
This allows uploaded lab files to be viewable:
bashphp artisan storage:link

Step 12: Start the Laravel development server
bashphp artisan serve
You should see:
   INFO  Server running on [http://127.0.0.1:8000].
Leave this terminal window open while testing.

Step 13: Open the app in browser
Open your browser and go to:
http://localhost:8000
You should see the PIMS login page!

Step 14: Log in with demo accounts
All demo accounts use password: password123
RoleEmailSuper Adminadmin@pims.testDoctordoctor@pims.testReceptionistrecept@pims.testMedTechmedtech@pims.testPatientpatient@pims.test

Troubleshooting
"Command not found: composer"

Composer not installed. Download from getcomposer.org

"mysql: command not found"

MySQL not installed or not in PATH. Use phpMyAdmin or MySQL Workbench GUI instead to import SQL files.

"SQLSTATE[HY000]: General error: 1030 Got error..."

Database wasn't imported correctly. Delete the database and repeat steps 8-9.

"Port 8000 already in use"

Another app is using port 8000. Run: php artisan serve --port=8001

Login fails

Confirm both SQL files were imported in the correct order (schema first, then seed).

