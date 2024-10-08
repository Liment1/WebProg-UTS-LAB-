<?php
session_start();

if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}


if (!isset($_SESSION['todo_lists'])) {
    $_SESSION['todo_lists'] = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_list':
            $newList = [
                'id' => uniqid(),
                'title' => $_POST['title'],
                'tasks' => []
            ];
            $_SESSION['todo_lists'][] = $newList;
            echo json_encode(['status' => 'success', 'message' => 'List deleted successfully.']);
            exit;

        case 'delete_list':
            $listId = $_POST['list_id'];
            $_SESSION['todo_lists'] = array_filter($_SESSION['todo_lists'], function($list) use ($listId) {
                return $list['id'] !== $listId;
            });
            echo json_encode(['status' => 'success', 'message' => 'Daftar berhasil dihapus.']);
            exit;

        case 'add_task':
            $listId = $_POST['list_id'];
            $newTask = [
                'id' => uniqid(),
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'due_date' => $_POST['due_date'],
                'completed' => false
            ];
            foreach ($_SESSION['todo_lists'] as &$list) {
                if ($list['id'] === $listId) {
                    $list['tasks'][] = $newTask;
                    break;
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Task added successfully.']);
            exit;

        case 'complete_task':
            $taskId = $_POST['task_id'];
            foreach ($_SESSION['todo_lists'] as &$list) {
                foreach ($list['tasks'] as &$task) {
                    if ($task['id'] === $taskId) {
                        $task['completed'] = true;
                        break 2;
                    }
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Task marked as complete.']);
            exit;
    }
}

$lists = $_SESSION['todo_lists'];


$searchQuery = $_GET['search'] ?? '';
$filterStatus = $_GET['filter'] ?? 'all';


function filterTasks($tasks, $searchQuery, $filterStatus) {
    return array_filter($tasks, function($task) use ($searchQuery, $filterStatus) {
       
        $matchesSearch = empty($searchQuery) || 
            stripos($task['title'], $searchQuery) !== false || 
            stripos($task['description'], $searchQuery) !== false;

       
        $matchesFilter = $filterStatus === 'all' || 
            ($filterStatus === 'completed' && $task['completed']) || 
            ($filterStatus === 'incomplete' && !$task['completed']);

        return $matchesSearch && $matchesFilter;
    });
}


foreach ($lists as &$list) {
    $list['tasks'] = filterTasks($list['tasks'], $searchQuery, $filterStatus);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Welcome , <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <div>
                <button onclick="showCreateListModal()" class="bg-blue-500 text-white px-4 py-2 rounded mr-2 hover:bg-blue-600">Create New To-Do List</button>
                <a href="dashboard.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Dashboard</a>
                <a class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" href="profile.php">My Profile</a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
       
        <div class="mb-6 bg-white p-4 rounded shadow">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <input type="text" 
                           name="search" 
                           placeholder="Search tasks by title or description..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:w-48">
                    <select name="filter" 
                            onchange="this.form.submit()" 
                            class="w-full px-4 py-2 rounded border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Task</option>
                        <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed Tasks</option>
                        <option value="incomplete" <?php echo $filterStatus === 'incomplete' ? 'selected' : ''; ?>>Incomplete Tasks</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    Find
                </button>
            </form>
        </div>

       
        <div class="mb-4 text-gray-600">
            <?php
            $totalTasks = 0;
            foreach ($lists as $list) {
                $totalTasks += count($list['tasks']);
            }
            if ($searchQuery) {
                echo "<p>Showing results for: \"" . htmlspecialchars($searchQuery) . "\"</p>";
            }
            echo "<p>Found $totalTasks Tasks " . ($filterStatus !== 'all' ? $filterStatus : '') . "</p>";
            ?>
        </div>

       
        <?php if (empty($lists)): ?>
            <p class="text-center text-gray-600">No to-do lists found. Create a new list to get started!</p>
        <?php else: ?>
            <?php foreach ($lists as $list): ?>
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
                            <?php
                            if ($searchQuery || $filterStatus !== 'all') {
                                echo "No tasks match your current filters.";
                            } else {
                                echo "No tasks in this list yet.";
                            }
                            ?>
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
                                        <button onclick="completeTask('<?php echo $task['id']; ?>')" 
                                                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                                Complete
                                        </button>
                                    <?php endif; ?>
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
        
        function showCreateListModal() {
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
                    Swal.fire('Successe!', data.message, 'success').then(() => {
                        closeAddTaskModal();
                        location.reload();
                    });
                } else {
                    Swal.fire('Warning!', 'Cannot Create List. Please Try Again.', 'error');
                }
            }).fail(function() {
                Swal.fire('Warning!', 'Cannot Create List. Please Try Again.', 'error');
            });
        });

        function deleteList(listId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, Delete It!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('', { action: 'delete_list', list_id: listId }, function(response) {
                        var data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire('Success!', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Warning!', 'Cannot Create List. Please Try Again.', 'error');
                        }
                    }).fail(function() {
                        Swal.fire('Warning!', 'Cannot Create List. Please Try Again.', 'error');
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
