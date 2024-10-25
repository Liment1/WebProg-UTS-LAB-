<?php
session_start();

$conn = new mysqli('localhost', 'evef9533_admin123','_z9K7Nrih7acMEu', 'evef9533_TodoList');

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}
if (!isset($_SESSION['user_email'])) {
    error_log("No user_email in session, redirecting to login.php");
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_id, $user_name);
$stmt->fetch();
$stmt->close();

$_SESSION['user_name'] = $user_name;
$_SESSION['user_id'] = $user_id;

$searchQuery = $_GET['search'] ?? ''; 
if (trim($searchQuery) == "")
    $searchQuery = NULL;
$filterStatus = $_GET['filter'] ?? 'all'; 

// Step 1: Fetch all to-do lists
$stmt = $conn->prepare("
    SELECT l.id as list_id, l.title as list_title 
    FROM todo l 
    WHERE l.user_id = ?
");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize $todo_lists with lists, even without tasks
$todo_lists = [];
while ($row = $result->fetch_assoc()) {
    $todo_lists[$row['list_id']] = [
        'id' => $row['list_id'],
        'title' => $row['list_title'],
        'tasks' => [] // Set tasks as an empty array initially
    ];
}

// Step 2: Fetch tasks based on search query and filter, if any
if (!empty($searchQuery)) {
    $searchPattern = '%' . $searchQuery . '%';
    $stmt = $conn->prepare("
        SELECT t.id as task_id, t.title as task_title, t.description, t.due_date, t.completed, l.id as list_id 
        FROM tasks t 
        JOIN todo l ON t.list_id = l.id 
        WHERE l.user_id = ? AND t.title LIKE ?
    ");
    $stmt->bind_param('ss', $user_id, $searchPattern);
} else {
    $stmt = $conn->prepare("
        SELECT t.id as task_id, t.title as task_title, t.description, t.due_date, t.completed, l.id as list_id 
        FROM tasks t 
        JOIN todo l ON t.list_id = l.id 
        WHERE l.user_id = ?
    ");
    $stmt->bind_param('s', $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Step 3: Add tasks to corresponding lists
while ($row = $result->fetch_assoc()) {
    $taskMatchesFilter = $filterStatus === 'all' ||
        ($filterStatus === 'completed' && $row['completed']) ||
        ($filterStatus === 'incomplete' && !$row['completed']);

    if ($taskMatchesFilter) {
        $todo_lists[$row['list_id']]['tasks'][] = [
            'id' => $row['task_id'],
            'title' => $row['task_title'],
            'description' => $row['description'],
            'due_date' => $row['due_date'],
            'completed' => (bool)$row['completed']
        ];
    }
}

$stmt->close();

// Handle POST requests for deleting tasks and lists
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    error_log("Action received in POST request: " . $action);

    switch ($action) {
        case 'delete_task':
            $taskId = $_POST['task_id'];
            error_log("Deleting task with ID: " . $taskId);

            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->bind_param('s', $taskId);
            $stmt->execute();
            $stmt->close();

            error_log("Task deleted from database with ID: " . $taskId);
            echo json_encode(['status' => 'success', 'message' => 'Task deleted successfully.']);
            exit;

        case 'delete_list':
            $listId = $_POST['list_id'];
            error_log("Deleting list with ID: " . $listId);

            // Delete all tasks in this list
            $stmt = $conn->prepare("DELETE FROM tasks WHERE list_id = ?");
            $stmt->bind_param('s', $listId);
            $stmt->execute();
            $stmt->close();

            // Delete the list itself
            $stmt = $conn->prepare("DELETE FROM todo WHERE id = ?");
            $stmt->bind_param('s', $listId);
            $stmt->execute();
            $stmt->close();

            error_log("List and associated tasks deleted from database with ID: " . $listId);
            echo json_encode(['status' => 'success', 'message' => 'List and tasks deleted successfully.']);
            exit;
    }
}


function generateNewId($conn, $table, $prefix) {
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(id, 2) AS UNSIGNED)) as max_id FROM $table");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $maxId = $row['max_id'];

    if ($maxId) {
        $newNumericPart = $maxId + 1;
        return $prefix . str_pad($newNumericPart, 4, '0', STR_PAD_LEFT);
    } else {
        return $prefix . '0001'; 
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    error_log("Action received in POST request: " . $action);

    switch ($action) {
        case 'create_list':
            $newTitle = trim($_POST['title']);
            error_log("Creating new list with title: " . $newTitle);

            $newListId = generateNewId($conn, 'todo', 'D'); // Generate new list ID with D0001 format
            $stmt = $conn->prepare("INSERT INTO todo (id, user_id, title) VALUES (?, (SELECT id FROM users WHERE email = ?), ?)");
            $stmt->bind_param('sss', $newListId, $user_email, $newTitle);
            $stmt->execute();
            $stmt->close();

            error_log("New list created in database with title: " . $newTitle);
            echo json_encode(['status' => 'success', 'message' => 'List created successfully.']);
            exit;

        case 'delete_list':
            $listId = $_POST['list_id'];
            error_log("Deleting list with ID: " . $listId);

            $stmt = $conn->prepare("DELETE FROM todo WHERE id = ?");
            $stmt->bind_param('s', $listId); // Adjust to use 's' for string id
            $stmt->execute();
            $stmt->close();

            error_log("List deleted from database with ID: " . $listId);
            echo json_encode(['status' => 'success', 'message' => 'List deleted successfully.']);
            exit;

        case 'add_task':
            $listId = $_POST['list_id'];
            error_log("Adding task to list with ID: " . $listId);

            $newTaskId = generateNewId($conn, 'tasks', 'A'); // Generate new task ID with A0001 format
            $newTask = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'due_date' => $_POST['due_date'],
                'completed' => 0
            ];

            $stmt = $conn->prepare("INSERT INTO tasks (id, list_id, title, description, due_date, completed) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param('sssss', $newTaskId, $listId, $newTask['title'], $newTask['description'], $newTask['due_date']);
            $stmt->execute();
            $stmt->close();

            error_log("New task added to database for list: " . $listId);
            echo json_encode(['status' => 'success', 'message' => 'Task added successfully.']);
            exit;

        case 'complete_task':
            $taskId = $_POST['task_id'];
            error_log("Completing task with ID: " . $taskId);

            $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ?");
            $stmt->bind_param('s', $taskId);
            $stmt->execute();
            $stmt->close();

            error_log("Task marked as complete in database with ID: " . $taskId);
            echo json_encode(['status' => 'success', 'message' => 'Task marked as complete.']);
            exit;
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
<nav class="bg-green-600 shadow-lg">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo and Welcome text -->
        <h1 class="text-2xl font-bold text-white">Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
        
        <!-- Hamburger button (for small screens) -->
        <div class="md:hidden">
            <button id="navbar-toggle" class="text-white focus:outline-none">
                <svg class="h-6 w-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M3 5h18a1 1 0 010 2H3a1 1 0 010-2zm0 6h18a1 1 0 010 2H3a1 1 0 010-2zm0 6h18a1 1 0 010 2H3a1 1 0 010-2z" />
                </svg>
            </button>
        </div>

        <!-- Navbar links (hidden on small screens, shown on medium and larger screens) -->
        <div class="hidden md:flex space-x-4">
            <button onclick="showCreateListModal()" 
                    class="bg-white text-green-600 font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-50 transition duration-300 ease-in-out">
                Create New To-Do List
            </button>
            <a href="dashboard.php" 
               class="bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-800 transition duration-300 ease-in-out">
                Dashboard
            </a>
            <a href="profile.php" 
               class="bg-green-800 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-900 transition duration-300 ease-in-out">
                My Profile
            </a>
            <a href="logout.php" 
               class="bg-red-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
                Logout
            </a>
        </div>
    </div>

    <!-- Collapsible menu for small screens -->
    <div id="navbar-menu" class="hidden md:hidden bg-green-600 px-6 py-4 space-y-2">
        <button onclick="showCreateListModal()" 
                class="block w-full text-left bg-white text-green-600 font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-50 transition duration-300 ease-in-out">
            Create New To-Do List
        </button>
        <a href="dashboard.php" 
           class="block w-full text-left bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-800 transition duration-300 ease-in-out">
            Dashboard
        </a>
        <a href="profile.php" 
           class="block w-full text-left bg-green-800 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-900 transition duration-300 ease-in-out">
            My Profile
        </a>
        <a href="logout.php" 
           class="block w-full text-left bg-red-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
            Logout
        </a>
    </div>
</nav>

<script>
    // Script to toggle the visibility of the navbar menu on small screens
    document.getElementById('navbar-toggle').addEventListener('click', function() {
        const menu = document.getElementById('navbar-menu');
        menu.classList.toggle('hidden');
    });

    // Reverting to the original modal functionality
    function showCreateListModal() {
        document.getElementById('createListModal').classList.remove('hidden');
    }
</script>

    <div class="container mx-auto px-4 py-8">
        <!-- Search and Filter Form -->
        <div class="mb-6 bg-white p-4 rounded shadow">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" 
                           name="search" 
                           placeholder="Search tasks by title or description..." 
                           value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" 
                           class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:w-48">
                    <select name="filter" 
                            onchange="this.form.submit()" 
                            class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Tasks</option>
                        <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed Tasks</option>
                        <option value="incomplete" <?php echo $filterStatus === 'incomplete' ? 'selected' : ''; ?>>Incomplete Tasks</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    Find
                </button>
            </form>
        </div>

        <!-- Results Section -->
        <div class="mb-4 text-gray-600">
            <?php
            $totalTasks = 0;
            foreach ($todo_lists as $list) {
                $totalTasks += count($list['tasks']);
            }
            echo "<p>Found $totalTasks Task(s)</p>";
            ?>
        </div>

        <!-- To-Do List Display -->
        <?php if (empty($todo_lists)): ?>
            <p class="text-center text-gray-600">No to-do lists found. Create a new list to get started!</p>
        <?php else: ?>
            <?php foreach ($todo_lists as $list): ?>
                <div class="mb-8 bg-white p-6 rounded shadow">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($list['title']); ?></h2>
                        <div>
                            <button onclick="showAddTaskModal('<?php echo $list['id']; ?>')" 
                                    class="bg-green-500 text-white px-4 py-2 rounded mr-2 hover:bg-green-600">
                                Add Task
                            </button>
                            <button onclick="deleteList('<?php echo $list['id']; ?>')" 
                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                Delete List
                            </button>
                        </div>
                    </div>
                    <?php if (empty($list['tasks'])): ?>
                        <p class="text-center text-gray-600 py-4">
                            No tasks in this list yet.
                        </p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($list['tasks'] as $task): ?>
                                <div class="bg-gray-50 p-4 rounded shadow">
                                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($task['title']); ?></h3>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <p class="text-gray-500">Due: <?php echo htmlspecialchars($task['due_date']); ?></p>
                                    <p class="text-<?php echo $task['completed'] ? 'green' : 'red'; ?>-500">
                                        <?php echo $task['completed'] ? 'Completed' : 'Incomplete'; ?>
                                    </p>
                                    <?php if (!$task['completed']): ?>
                                        <button onclick="completeTask('<?php echo htmlspecialchars($task['id']); ?>')" 
                                                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                                Complete
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteTask('<?php echo htmlspecialchars($task['id']); ?>')" 
                                            class="mt-2 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                        Delete Task
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <div id="createListModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6">
                <h2 class="text-2xl font-semibold mb-4">Create New To-Do List</h2>
                <form id="createListForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">List Title</label>
                        <input type="text" name="title" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeCreateListModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2">Cancel</button>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Create List</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="addTaskModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6">
                <h2 class="text-2xl font-semibold mb-4">Add New Task</h2>
                <form id="addTaskForm">
                    <input type="hidden" name="list_id" id="taskListId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" name="due_date" class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="closeAddTaskModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2">Cancel</button>
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
          document.getElementById('navbar-toggle').addEventListener('click', function() {
        const menu = document.getElementById('navbar-menu');
        menu.classList.toggle('hidden');
    });
        function showCreateListModal() {
            document.getElementById('createListForm').reset(); 
            document.getElementById('createListModal').classList.remove('hidden');
        }

        function closeCreateListModal() {
            document.getElementById('createListModal').classList.add('hidden');
        }

        function showAddTaskModal(listId) {
            document.getElementById('taskListId').value = listId;
            document.getElementById('addTaskModal').classList.remove('hidden');
        }

        function closeAddTaskModal() {
            document.getElementById('addTaskModal').classList.add('hidden');
        }

        $('#createListForm').submit(function(event) {
            event.preventDefault();
            $.post('', $(this).serialize() + '&action=create_list', function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        closeCreateListModal();
                        location.reload();
                    });
                } else {
                    Swal.fire('Warning!', 'Cannot Create List. Please Try Again Later.', 'error');
                }
            }).fail(function() {
                Swal.fire('Warning!', 'Cannot Create List. Please Try Again Later.', 'error');
            });
        });

        $('#addTaskForm').submit(function(event) {
            event.preventDefault();
            $.post('', $(this).serialize() + '&action=add_task', function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        closeAddTaskModal();
                        location.reload();
                    });
                } else {
                    Swal.fire('Warning!', 'Cannot Add Task. Please Try Again.', 'error');
                }
            }).fail(function() {
                Swal.fire('Warning!', 'Cannot Add Task. Please Try Again.', 'error');
            });
        });

        function deleteTask(taskId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This task will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', { action: 'delete_task', task_id: taskId }, function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', 'Failed to delete task.', 'error');
                        }
                    });
                }
            });
        }

        function deleteList(listId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This list and all associated tasks will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', { action: 'delete_list', list_id: listId }, function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', 'Failed to delete list.', 'error');
                        }
                    });
                }
            });
        }

        function completeTask(taskId) {
            $.post('', { action: 'complete_task', task_id: taskId }, function(response) {
                var data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Warning!', 'Failed to mark task as complete. Please Try Again.', 'error');
                }
            }).fail(function() {
                Swal.fire('Warning!', 'Failed to mark task as complete. Please Try Again.', 'error');
            });
        }
    </script>
</body>
</html>
