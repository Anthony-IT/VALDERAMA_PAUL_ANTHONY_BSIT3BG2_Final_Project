<?php
session_start();

// Initialize XML
$xmlFile = 'users.xml';
if (!file_exists($xmlFile)) {
    $xml = new SimpleXMLElement('<users></users>');
    $xml->asXML($xmlFile);
}

function loadUsers() {
    return simplexml_load_file('users.xml');
}

function saveUsers($xml) {
    $xml->asXML('users.xml');
}

function findUserByEmail($xml, $email) {
    foreach ($xml->user as $user) {
        if ((string)$user->email === $email) return $user;
    }
    return null;
}

// Login Handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $xml = loadUsers();
    $user = findUserByEmail($xml, $email);

    if ($user && password_verify($password, (string)$user->password)) {
        $_SESSION['user_name'] = (string)$user->name;
        $_SESSION['course'] = (string)$user->course;
        echo "<script>alert('Login successful!'); window.location.href='user_dashboard.php';</script>";
        exit;
    } else {
        echo "<script>alert('Invalid login credentials.');</script>";
    }
}

// Sign-up Handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $xml = loadUsers();

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $course = trim($_POST['course']);
    $profile_pic = '';

    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match.');</script>";
    } elseif (findUserByEmail($xml, $email)) {
        echo "<script>alert('Email already registered.');</script>";
    } else {
        if (!empty($_FILES['profile_pic']['name'])) {
            $dir = 'uploads/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $filename = time() . '_' . basename($_FILES["profile_pic"]["name"]);
            $filepath = $dir . $filename;
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $filepath)) {
                $profile_pic = $filepath;
            }
        }

        $user = $xml->addChild('user');
        $user->addChild('name', htmlspecialchars($name));
        $user->addChild('email', htmlspecialchars($email));
        $user->addChild('password', password_hash($password, PASSWORD_DEFAULT));
        $user->addChild('course', htmlspecialchars($course));
        $user->addChild('profile_pic', $profile_pic);

        saveUsers($xml);
        echo "<script>alert('Sign-up successful! Please log in.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Sign-Up</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right,rgb(163, 144, 103), #ACB6E5);
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 400px;
            margin: 5% auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: none;
        }
        form.active {
            display: block;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #5C6BC0;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #3f51b5;
        }
        .toggle {
            text-align: center;
            margin-top: 15px;
        }
        .toggle a {
            color: #5C6BC0;
            text-decoration: none;
            font-weight: bold;
        }
        .toggle a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <h2 id="formTitle">Login</h2>

    <!-- Login Form -->
    <form id="loginForm" method="POST" class="active">
        <input type="email" name="email" required placeholder="Email">
        <input type="password" name="password" required placeholder="Password">
        <button type="submit" name="login">Login</button>
    </form>

    <!-- Signup Form -->
    <form id="signupForm" method="POST" enctype="multipart/form-data">
        <input type="text" name="name" required placeholder="Name">
        <input type="email" name="email" required placeholder="Email">
        <input type="password" name="password" required placeholder="Password">
        <input type="password" name="confirm_password" required placeholder="Confirm Password">
        <input type="text" name="course" required placeholder="Course">
        <input type="file" name="profile_pic">
        <button type="submit" name="signup">Sign Up</button>
    </form>

    <div class="toggle">
        <span id="toggleText">Don't have an account?</span>
        <a href="javascript:void(0);" onclick="toggleForm()">Sign Up</a>
    </div>
</div>

<script>
    function toggleForm() {
        const loginForm = document.getElementById("loginForm");
        const signupForm = document.getElementById("signupForm");
        const formTitle = document.getElementById("formTitle");
        const toggleText = document.getElementById("toggleText");
        const toggleLink = document.querySelector(".toggle a");

        if (loginForm.classList.contains("active")) {
            loginForm.classList.remove("active");
            signupForm.classList.add("active");
            formTitle.innerText = "Sign Up";
            toggleText.innerText = "Already have an account?";
            toggleLink.innerText = "Login";
        } else {
            signupForm.classList.remove("active");
            loginForm.classList.add("active");
            formTitle.innerText = "Login";
            toggleText.innerText = "Don't have an account?";
            toggleLink.innerText = "Sign Up";
        }
    }
</script>

</body>
</html>
