
<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (empty($_SESSION['captcha_answer'])) {
    $_SESSION['captcha_answer'] = random_int(1, 9)
        + random_int(1, 9);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = 'Enter a name, a valid email, and a password of at least 8 characters.';
    } elseif (
        $captcha === '' ||
        !ctype_digit($captcha) ||
        (int) $captcha !== (int) $_SESSION['captcha_answer']
    ) {
        $error = 'Incorrect CAPTCHA answer. Please try again.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $existingUser = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existingUser) {
            $error = 'This email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {
                $success = 'Registration successful! You can now log in.';
                $_POST = [];
            } else {
                $error = 'Registration failed. Please try again.';
            }

            $stmt->close();
        }
    }

    // Generate a new CAPTCHA after every submission.
    $_SESSION['captcha_answer'] = random_int(1, 9)
        + random_int(1, 9);
}

$firstNumber = random_int(1, 9);
$secondNumber = random_int(1, 9);
$_SESSION['captcha_answer'] = $firstNumber + $secondNumber;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5fa;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 420px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px #ddd;
        }

        h1 {
            text-align: center;
            color: #5c4ac7;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #5c4ac7;
            color: white;
            cursor: pointer;
        }

        .error {
            color: #b00020;
        }

        .success {
            color: #16743b;
        }

        .back {
            color: #5c4ac7;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">
    <a class="back" href="index.php">&larr; Back to store</a>

    <h1>Create Account</h1>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <p><a href="login.php">Go to Login</a></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label for="name">Full Name</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
            required
        >

        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="150"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >

        <label for="password">Password (minimum 8 characters)</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
        >

        <label for="captcha">
            CAPTCHA: <?= $firstNumber ?> + <?= $secondNumber ?> = ?
        </label>
        <input
            type="number"
            id="captcha"
            name="captcha"
            required
        >

        <button type="submit">Register</button>
    </form>

    <p>Already registered? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
