<?php
session_start();

$xml_file = 'users.xml';
if (!file_exists($xml_file)) {
    $xml = new SimpleXMLElement('<users></users>');
    $xml->asXML($xml_file);
}
$xml = simplexml_load_file($xml_file);

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $index = 0;
    foreach ($xml->user as $user) {
        if ((int)$user->id == $user_id) {
            unset($xml->user[$index]);
            file_put_contents($xml_file, $xml->asXML());
            $_SESSION['message'] = "User deleted successfully!";
            header("Location: user_dashboard.php");
            exit();
        }
        $index++;
    }
}

// Handle new user addition
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $course = trim($_POST['course']);
    $profile_pic = '';

    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $profile_pic = $target_dir . $filename;
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $profile_pic);
    }

    $user = $xml->addChild('user');
    $user->addChild('id', time());
    $user->addChild('name', htmlspecialchars($name));
    $user->addChild('course', htmlspecialchars($course));
    $user->addChild('profile_pic', htmlspecialchars($profile_pic));

    $xml->asXML($xml_file);
    $_SESSION['message'] = "User added successfully!";
    header("Location: user_dashboard.php");
    exit;
}

// Search + pagination
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$filtered_users = [];

foreach ($xml->user as $user) {
    if (stripos((string)$user->name, $search_query) !== false || $search_query === '') {
        $filtered_users[] = $user;
    }
}

$results_per_page = 4;
$total_rows = count($filtered_users);
$total_pages = ceil($total_rows / $results_per_page);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;
$users_to_display = array_slice($filtered_users, $offset, $results_per_page);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $name = trim($_POST['edit_name']);
    $course = trim($_POST['edit_course']);
    $profile_pic = $_POST['current_pic'];

    if (!empty($_FILES['edit_profile_pic']['name']) && $_FILES['edit_profile_pic']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = time() . "_" . basename($_FILES["edit_profile_pic"]["name"]);
        $profile_pic = $target_dir . $filename;
        move_uploaded_file($_FILES["edit_profile_pic"]["tmp_name"], $profile_pic);
    }

    foreach ($xml->user as $user) {
        if ((int)$user->id == $user_id) {
            $user->name = htmlspecialchars($name);
            $user->course = htmlspecialchars($course);
            $user->profile_pic = htmlspecialchars($profile_pic);
            break;
        }
    }

    $xml->asXML($xml_file);
    $_SESSION['message'] = "User updated successfully!";
    header("Location: user_dashboard.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Record</title>
    <style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f2f0eb;         
    display:flex;
}


.sidebar{
    width:22rem;
    min-height:100vh;
    background:#fff8ed;          
    padding:25px 30px;
    box-shadow:2px 0 10px rgba(0,0,0,0.08);
}

.sidebar h2{
    font-size:22px;
    font-weight:600;
    text-align:center;
    margin-bottom:15px;
    color:#333;
}

.sidebar label{
    font-size:13px;
    font-weight:600;
    color:#444;
}

.sidebar input[type="text"],
.sidebar input[type="file"]{
    width:100%;
    padding:9px 10px;
    margin:6px 0 12px;
    border:1px solid #d1cfc7;
    border-radius:6px;
    background:#fff;
    font-size:14px;
}

.sidebar button{
    width:100%;
    padding:10px 0;
    background:#c5ad93;          
    border:none;
    border-radius:6px;
    color:#fff;
    font-weight:bold;
    letter-spacing:.5px;
    cursor:pointer;
    transition:opacity .25s;
}
.sidebar button:hover{opacity:.85}


.main-content{
    flex:1;
    display:flex;
    flex-direction:column;
}


.header{
    background:#c5ad93;          
    padding:18px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 2px 6px rgba(0,0,0,.15);
}

.logo{
    line-height:1.2;
}
.logo p:first-child{
    font-size:26px;
    font-weight:700;
}
.logo p:last-child{
    font-size:13px;
}

.header nav{
    display:flex;
    gap:24px;
}
.header nav a{
    text-decoration:none;
    color:#000;
    font-weight:600;
    padding:6px 14px;
    border-radius:5px;
    transition:background .25s;
}
.header nav a:hover{
    background:#8c705e;
    color:#fff;
}


form[action=""]{
    margin:25px auto 15px;
    display:flex;
    gap:10px;
    align-items:center;
    width:90%;
}
form[action=""] input{
    flex:1;
    padding:8px 10px;
    border:1px solid #d1cfc7;
    border-radius:5px;
}
form[action=""] button{
    padding:8px 14px;
    background:#c5ad93;
    border:none;
    color:#fff;
    font-weight:600;
    border-radius:5px;
    cursor:pointer;
}
.message{
    width:90%;
    margin:10px auto;
    background:#e8f6e8;
    color:#216c35;
    border:1px solid #bce0bc;
    padding:10px;
    border-radius:6px;
    text-align:center;
}

table{
    width:90%;
    margin:0 auto 25px;
    border-collapse:collapse;
    background:#fff;
    box-shadow:0 1px 6px rgba(0,0,0,.08);
}
thead{
    background:#f5ece1;
}
th,td{
    border:1px solid #d9d4cb;
    padding:12px 10px;
    text-align:center;
    font-size:14px;
}
table img{
    width:42px;
    height:42px;
    border-radius:50%;
    object-fit:cover;
}
td form{
    display:inline;
}
td button{
    background:#8c705e;
    border:none;
    color:#fff;
    padding:6px 10px;
    border-radius:4px;
    cursor:pointer;
}

.pagination{
    text-align:center;
    margin-bottom:25px;
}
.pagination a{
    display:inline-block;
    margin:0 4px;
    padding:6px 12px;
    background:#c5ad93;
    color:#000;
    border-radius:4px;
    text-decoration:none;
    font-weight:600;
    transition:background .25s;
}
.pagination a:hover{
    background:#8c705e;
    color:#fff;
}
.pagination .active{
    background:#8c705e;
    color:#fff;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff5e1;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #ccc;
    width: 40%;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.modal-content h2 {
    margin-bottom: 15px;
}

.modal-content input, .modal-content button {
    display: block;
    width: 100%;
    margin-top: 10px;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #aaa;
    font-size: 14px;
}

.modal-content button {
    background-color: #00bf72;
    color: white;
    font-weight: bold;
    border: none;
}

.close {
    float: right;
    font-size: 24px;
    cursor: pointer;
}

</style>


   
</head>
<body>
    <div class="sidebar">
        <h2>Add User</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="course" placeholder="Course" required>
            <input type="file" name="profile_pic">
            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="logo">
                <p>Y & P</p>
                <p>Yhuri Evangelista<br>Paul Anthony F. Valderama</p>
            </div>
            <nav>
                <a href="user_dashboard.php">Home</a>
                <a href="Aboutus.html">About Us</a>
                <a href="login_user.php">Log Out</a>
            </nav>
        </div>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="message"> <?= $_SESSION['message']; unset($_SESSION['message']); ?> </div>
        <?php endif; ?>

        <form method="GET" action="">
            <input type="text" name="search_query" placeholder="Search by name" value="<?= htmlspecialchars($search_query); ?>">
            <button type="submit">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Profile</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_to_display as $user): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($user->profile_pic) ?>" alt="Profile"></td>
                        <td><?= htmlspecialchars($user->id) ?></td>
                        <td><?= htmlspecialchars($user->name) ?></td>
                        <td><?= htmlspecialchars($user->course) ?></td>
                        <td>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="user_id" value="<?= $user->id ?>">
        <button type="submit" name="delete_user">Delete</button>
    </form>
    <button onclick="openEditModal('<?= $user->id ?>', '<?= htmlspecialchars($user->name, ENT_QUOTES) ?>', '<?= htmlspecialchars($user->course, ENT_QUOTES) ?>', '<?= htmlspecialchars($user->profile_pic, ENT_QUOTES) ?>')">Edit</button>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit User</h2>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="edit_user_id">
            <input type="hidden" name="current_pic" id="edit_current_pic">

            <label for="edit_name">Name:</label>
            <input type="text" name="edit_name" id="edit_name" required>

            <label for="edit_course">Course:</label>
            <input type="text" name="edit_course" id="edit_course" required>

            <label for="edit_profile_pic">Profile Picture:</label>
            <input type="file" name="edit_profile_pic" id="edit_profile_pic">

            <button type="submit" name="edit_user">Update User</button>
        </form>
    </div>
</div>


        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a class="<?= ($i == $page) ? 'active' : '' ?>" href="?page=<?= $i ?>&search_query=<?= htmlspecialchars($search_query) ?>"> <?= $i ?> </a>
            <?php endfor; ?>
        </div>
    </div>
</body>

<script>
function openEditModal(id, name, course, profilePic) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_course').value = course;
    document.getElementById('edit_current_pic').value = profilePic;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</html>
