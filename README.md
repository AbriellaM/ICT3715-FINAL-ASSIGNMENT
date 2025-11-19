# ICT3715-FINAL-ASSIGNMENT
Amandla High School Locker System
To get the Amandla High School Locker System running on your machine, start by cloning the repository from GitHub and moving into the project folder. Once you’re inside, install the dependencies with Composer — this will pull in PHPMailer and any other libraries the system needs.
Next, set up your database. Import the provided schema into MySQL using phpMyAdmin or the command line, and then update the includes/dbconnect.php  file with your local database credentials. This ensures the application can connect to your database correctly.
You’ll also need to configure email notifications. Create a Gmail App Password and place it in includes/mailer.php  alongside your Gmail address. This allows PHPMailer to send messages securely through Gmail’s SMTP servers.
With the database and mailer configured, place the project folder inside your XAMPP htdocs directory. Start Apache and MySQL from the XAMPP  control panel, then open your browser and navigate to . You should now see the system running.
From here, you can test the workflow: parents can apply for lockers and upload proof of payment, while admins can allocate or cancel lockers and manage the waiting list. Notifications will be sent automatically — parents receive updates about their applications, and admins receive alerts and proof of payment files as attachments.
