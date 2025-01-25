# Poll - A Simple PHP-Based Voting System

**Description**:  
This repository contains a simple voting system that allows users to create polls and participate in voting. The application is developed with PHP and MySQL, offering an intuitive and user-friendly interface for polls and their results.

---

## 🚀 **Features**
1. **Creating Polls**:
   - Users can enter any question and add answer options.
   - At least two answer options must be defined.
2. **Voting**:
   - Visitors can vote on the provided options.
   - Security measures like IP address tracking and cookies prevent multiple votes from the same user.
3. **Result Display**:
   - Votes are updated in real-time and displayed as a bar chart.
   - Total votes and percentages are shown for each answer option.
4. **Poll Overview**:
   - All created polls are stored in the database and can be accessed as needed.
5. **Intuitive User Interface**:
   - Clean and simple design for the best user experience.
   - Supports the addition of options through dynamic input fields.
6. **Live Updates**:
   - Poll results are automatically refreshed every 2 seconds via AJAX/Polling.

---

## ⚙️ **Installation**

1. **Clone the Project**:
   ```bash
   git clone https://github.com/0eiki0/poll.git
   ```

2. **Database Setup**:
   - Import the provided SQL file (`strawpoll.sql`), which creates all necessary tables (`poll`, `poll_options`, `poll_votes`).
   - Use the MySQL terminal or a tool like PHPMyAdmin:
     ```sql
     CREATE DATABASE strawpoll;
     USE strawpoll;
     SOURCE strawpoll.sql;
     ```

3. **Adjust Configuration**:
   - Update the MySQL credentials in the `config.php` file:
     ```php
     $servername = "localhost";
     $username = "your_username";
     $password = "your_password";
     $dbname = "strawpoll";
     ```

4. **Host Project Locally or on a Server**:
   - Copy the project files to the root directory of your web server.  
     For example, in XAMPP: `htdocs/strawpoll`.
   - Visit `http://localhost/strawpoll/`.

---

## 📋 **Usage**
### 1. **Create a Poll**
   - A form appears on the homepage allowing users to enter a question and define options.
   - Additional options can be added or existing ones removed.
   - After submission, the user is redirected to the newly created poll.

### 2. **Participate in a Poll**
   - Participants select one of the available options and click "Vote".
   - After voting, poll results are displayed in real-time.

### 3. **View Results**
   - Anyone can view poll results once voting is complete or after participating.
   - Results are visualized as bar charts showing the number of votes and percentages.

---

## ❤️ **Contributions**
This project is a personal experiment and is open to suggestions and pull requests! 🎉  
Feel free to report bugs or propose new features.

---

## 📜 **License**
This project is licensed under the [MIT License](LICENSE.md). Feel free to use, modify, and improve! 😊
