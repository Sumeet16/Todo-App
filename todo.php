<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';  // Adjust based on your MySQL user
$pass = '';      // Adjust based on your MySQL password
$dbname = 'todo_app';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Signup
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    header('Location: todo.php');
    exit;
}

// Handle Login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: todo.php');
        exit;
    } else {
        $login_error = "Invalid username or password";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: todo.php');
    exit;
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login & Signup</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>

    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <h1 class="text-center">Welcome to Todo App</h1>

                    <!-- Login Form -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="card-title text-center">Login</h3>
                            <form action="todo.php" method="POST">
                                <div class="form-group">
                                    <label for="loginUsername">Username</label>
                                    <input type="text" name="username" id="loginUsername" class="form-control" placeholder="Enter your username" required>
                                </div>
                                <div class="form-group">
                                    <label for="loginPassword">Password</label>
                                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
                            </form>
                        </div>
                    </div>

                    <p class="text-center mt-3">Don't have an account? <a href="#" data-toggle="modal" data-target="#signupModal">Signup here</a></p>
                </div>
            </div>
        </div>

        <!-- Signup Modal -->
        <div class="modal fade" id="signupModal" tabindex="-1" role="dialog" aria-labelledby="signupModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="signupModalLabel">Signup</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="todo.php" method="POST">
                            <div class="form-group">
                                <label for="signupUsername">Username</label>
                                <input type="text" name="username" id="signupUsername" class="form-control" placeholder="Choose a username" required>
                            </div>
                            <div class="form-group">
                                <label for="signupPassword">Password</label>
                                <input type="password" name="password" id="signupPassword" class="form-control" placeholder="Choose a password" required>
                            </div>
                            <button type="submit" name="signup" class="btn btn-success btn-block">Signup</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap JS and dependencies -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>

    </html>

<?php
    exit;
}

// Handle Create and Update actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update an existing todo
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE todos SET title=?, description=?, due_date=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sssii', $title, $description, $due_date, $id, $_SESSION['user_id']);
    } else {
        // Create a new todo
        $stmt = $conn->prepare("INSERT INTO todos (title, description, due_date, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $title, $description, $due_date, $_SESSION['user_id']);
    }
    $stmt->execute();
    header('Location: todo.php');
    exit;
}

// Handle Delete action
if (isset($_GET['delete']) && isset($_SESSION['user_id'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM todos WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: todo.php');
    exit;
}

// Fetch the todo for editing (if needed)
$edit_todo = null;
if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM todos WHERE id=$id AND user_id={$_SESSION['user_id']}");
    $edit_todo = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo Application</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center">Todo List for <?= $_SESSION['username'] ?></h1>
        <a href="todo.php?logout=true" class="btn btn-danger">Logout</a>

        <!-- Todo Form for Create/Edit -->
        <form action="todo.php" method="POST" class="mb-4">
            <input type="hidden" name="id" value="<?= $edit_todo['id'] ?? '' ?>">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" class="form-control" value="<?= $edit_todo['title'] ?? '' ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" class="form-control"><?= $edit_todo['description'] ?? '' ?></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?= $edit_todo['due_date'] ?? '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <?= isset($edit_todo) ? 'Update Todo' : 'Add Todo' ?>
            </button>
        </form>

        <!-- Display Todo List -->
        <h3>Current Todos</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM todos WHERE user_id={$_SESSION['user_id']}");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['title']}</td>
                    <td>{$row['description']}</td>
                    <td>{$row['due_date']}</td>
                    <td>{$row['status']}</td>
                    <td>
                        <a href='todo.php?edit={$row['id']}' class='btn btn-warning'>Edit</a>
                        <a href='todo.php?delete={$row['id']}' class='btn btn-danger'>Delete</a>
                    </td>
                </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>

</html>