# SocietyMS
The Society Maintenance Management System is a DBMS-based application that manages resident details, maintenance payments, expenses, and complaints in a residential society.

🛠️ Prerequisites

  -XAMPP (Make sure Apache and MySQL modules are running)

  -A web browser
  
  -Git (To clone the repository)

🚀 Setup & Installation
1. Clone the Project Folder
Go to your XAMPP installation folder using your terminal or command prompt:
cd C:\xampp\htdocs\

Clone the repository directly into the htdocs folder:
git clone https://github.com/meet0411/SocietyMS.git
This will create a new folder named SocietyMS inside htdocs containing all your project files.

2. Create the Database
(note: open XAMPP and Make sure Apache and MySQL modules are running)
Open your browser and go to the phpMyAdmin panel:
http://localhost/phpmyadmin

Steps:
1.Click on New to create a database.
2.Enter the database name: societyms
3.Click Create.
4.Navigate to the SQL tab at the top.
5.Paste and run the given SQL code in file database.sql to set up your primary tables.


🧪 How to Test the System
Step 1 – Register
-Go to: http://localhost/SocietyMS/register.php
-Fill in the registration form (Username, Email, Password).
-Click Register. The data will be saved securely in the societyms database.

Step 2 – Login
-Go to: http://localhost/SocietyMS/login.php
-Enter your registered Email and Password.
-If correct, the system will redirect you to the dashboard.

Step 3 – Session Working
-After login, $_SESSION['username'] stores the logged-in user.
-The Dashboard will remain accessible and retain your active session as you navigate the system (e.g., viewing bills or adding complaints).
-Unauthenticated users will be blocked and redirected to the login page.

Step 4 – Logout
-Click the Logout button.
-The session will be destroyed.
-You will be redirected back to the login page.
