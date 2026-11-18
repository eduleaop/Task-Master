
<?php
// ==========================================
// 1. CONEXÃO COM O BANCO DE DADOS
// ==========================================

$dbFile = __DIR__ . '/tasks.sqlite';

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Criar tabela
$pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    done INTEGER DEFAULT 0
)");

// Adicionar novas colunas caso ainda não existam
$columns = $pdo->query("PRAGMA table_info(tasks)")
               ->fetchAll(PDO::FETCH_COLUMN, 1);

if (!in_array('due_date', $columns)) {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN due_date TEXT");
}

if (!in_array('responsible', $columns)) {
    $pdo->exec("ALTER TABLE tasks ADD COLUMN responsible TEXT");
}


// ==========================================
// 2. CRIAR TAREFA
// ==========================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {

    $title = trim($_POST['title']);
    $dueDate = $_POST['due_date'] ?? '';
    $responsible = trim($_POST['responsible'] ?? '');

    if (empty($title)) {

        $error = "O título da tarefa não pode estar vazio!";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, due_date, responsible)
            VALUES (:title, :due_date, :responsible)
        ");

        $stmt->execute([
            ':title' => $title,
            ':due_date' => $dueDate,
            ':responsible' => $responsible
        ]);

        header("Location: index.php");
        exit;
    }
}


// ==========================================
// 3. CONCLUIR OU EXCLUIR TAREFA
// ==========================================

if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    if ($_GET['action'] === 'complete') {

        $stmt = $pdo->prepare(
            "UPDATE tasks SET done = 1 WHERE id = :id"
        );

        $stmt->execute([':id' => $id]);

    } elseif ($_GET['action'] === 'delete') {

        $stmt = $pdo->prepare(
            "DELETE FROM tasks WHERE id = :id"
        );

        $stmt->execute([':id' => $id]);
    }

    header("Location: index.php");
    exit;
}


// ==========================================
// 4. BUSCAR TAREFAS
// ==========================================

$stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Task Master</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            color: #333;
            display: flex;
            justify-content: center;
            padding: 50px 15px;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 750px;
        }

        h1 {
            font-size: 1.5rem;
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .error {
            color: #dc2626;
            background: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        /* FORMULÁRIO */

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #1d4ed8;
        }

        /* LISTA DE TAREFAS */

        .task-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .task-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .task-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex: 1;
            min-width: 0;
        }

        .task-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
        }

        .task-field strong {
            font-size: 12px;
            color: #6b7280;
        }

        .task-field span {
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .actions a {
            text-decoration: none;
            font-size: 18px;
        }

        .done span {
            text-decoration: line-through;
            color: #9ca3af;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 600px) {

            .task-item {
                align-items: flex-start;
                flex-direction: column;
            }

            .task-info {
                flex-wrap: wrap;
                width: 100%;
            }

            .task-field {
                flex: 1 1 40%;
            }

            .actions {
                align-self: flex-end;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <h1>Task Master</h1>

    <?php if ($error): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- FORMULÁRIO -->

    <form method="POST" action="index.php" class="form-group">

        <label for="title">Tarefa</label>

        <input
            type="text"
            id="title"
            name="title"
            placeholder="O que precisa ser feito?"
            autocomplete="off"
            required
        >

        <label for="due_date">Data de vencimento</label>

        <input
            type="date"
            id="due_date"
            name="due_date"
        >

        <label for="responsible">Responsável</label>

        <input
            type="text"
            id="responsible"
            name="responsible"
            placeholder="Nome da pessoa responsável"
        >

        <button type="submit">
            Adicionar tarefa
        </button>

    </form>


    <!-- LISTA DE TAREFAS -->

    <ul class="task-list">

        <?php foreach ($tasks as $task): ?>

            <li class="task-item <?php echo $task['done'] ? 'done' : ''; ?>">

                <div class="task-info">

                    <div class="task-field">

                        <strong>Tarefa</strong>

                        <span>
                            <?php echo htmlspecialchars($task['title']); ?>
                        </span>

                    </div>


                    <div class="task-field">

                        <strong>Data de vencimento</strong>

                        <span>
                            <?php
                            echo !empty($task['due_date'])
                                ? date('d/m/Y', strtotime($task['due_date']))
                                : 'Não informada';
                            ?>
                        </span>

                    </div>


                    <div class="task-field">

                        <strong>Responsável</strong>

                        <span>
                            <?php
                            echo !empty($task['responsible'])
                                ? htmlspecialchars($task['responsible'])
                                : 'Não informado';
                            ?>
                        </span>

                    </div>

                </div>


                <!-- AÇÕES -->

                <div class="actions">

                    <?php if (!$task['done']): ?>

                        <a
                            href="?action=complete&id=<?php echo $task['id']; ?>"
                            title="Concluir"
                        >
                            ✅
                        </a>

                    <?php endif; ?>


                    <a
                        href="?action=delete&id=<?php echo $task['id']; ?>"
                        onclick="return confirm('Tem certeza que deseja excluir esta tarefa?');"
                        title="Excluir"
                    >
                        ❌
                    </a>

                </div>

            </li>

        <?php endforeach; ?>

    </ul>

</div>

</body>
</html>
