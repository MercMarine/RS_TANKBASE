<?php
$dbPath = __DIR__ . '/../data/tanks.db';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS tanks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        nation TEXT NOT NULL,
        class TEXT NOT NULL,
        year INTEGER,
        description TEXT
    )'
);

$errors = [];
$notice = null;

$classes = ['MBT', 'Light', 'Medium', 'Heavy', 'SPG', 'TD'];
$nations = ['USSR/Russia', 'USA', 'Germany', 'UK', 'France', 'China', 'Japan', 'Other'];

function field(string $key): string
{
    return trim($_POST[$key] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $payload = [
            'name' => field('name'),
            'nation' => field('nation') ?: 'Other',
            'class' => field('class') ?: 'MBT',
            'year' => field('year') !== '' ? (int) field('year') : null,
            'description' => field('description'),
        ];

        if ($payload['name'] === '') {
            $errors[] = 'Название обязательно.';
        }

        if (!$errors && $action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO tanks (name, nation, class, year, description)
                 VALUES (:name, :nation, :class, :year, :description)'
            );
            $stmt->execute($payload);
            $notice = 'Танк добавлен.';
        }

        if (!$errors && $action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                'UPDATE tanks
                 SET name = :name, nation = :nation, class = :class, year = :year, description = :description
                 WHERE id = :id'
            );
            $payload['id'] = $id;
            $stmt->execute($payload);
            $notice = 'Запись обновлена.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM tanks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $notice = 'Запись удалена.';
    }
}

$rows = $pdo->query('SELECT * FROM tanks ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Танковая база</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <h1>Танковая база</h1>

    <?php if ($notice): ?>
        <div class="card" style="border-color:#22c55e">✅ <?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="card" style="border-color:#ef4444">
            <?php foreach ($errors as $err): ?>
                <div>⚠️ <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Добавить танк</h2>
        <form method="post">
            <input type="hidden" name="action" value="create">
            <label>
                Название
                <input name="name" placeholder="Т-90М" required>
            </label>
            <label>
                Страна
                <select name="nation">
                    <?php foreach ($nations as $nation): ?>
                        <option value="<?= htmlspecialchars($nation) ?>"><?= htmlspecialchars($nation) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Класс
                <select name="class">
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Год
                <input name="year" type="number" min="1900" max="2100" placeholder="2020">
            </label>
            <label>
                Описание
                <textarea name="description" rows="3" placeholder="Коротко о машине"></textarea>
            </label>
            <button type="submit">Сохранить</button>
        </form>
    </div>

    <div class="card">
        <h2>Список</h2>
        <?php if (!$rows): ?>
            <p class="muted">Пока пусто. Добавьте первый танк.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Название</th>
                        <th>Страна</th>
                        <th>Класс</th>
                        <th>Год</th>
                        <th>Описание</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $tank): ?>
                        <tr>
                            <td><?= $tank['id'] ?></td>
                            <td>
                                <input form="edit-<?= $tank['id'] ?>" name="name" value="<?= htmlspecialchars($tank['name']) ?>">
                            </td>
                            <td>
                                <select form="edit-<?= $tank['id'] ?>" name="nation">
                                    <?php foreach ($nations as $nation): ?>
                                        <option value="<?= htmlspecialchars($nation) ?>" <?= $nation === $tank['nation'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($nation) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select form="edit-<?= $tank['id'] ?>" name="class">
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= htmlspecialchars($class) ?>" <?= $class === $tank['class'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input form="edit-<?= $tank['id'] ?>" name="year" type="number" min="1900" max="2100" value="<?= htmlspecialchars((string) $tank['year']) ?>">
                            </td>
                            <td>
                                <textarea form="edit-<?= $tank['id'] ?>" name="description" rows="2"><?= htmlspecialchars($tank['description']) ?></textarea>
                            </td>
                            <td>
                                <div class="actions">
                                    <form id="edit-<?= $tank['id'] ?>" method="post">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $tank['id'] ?>">
                                        <button type="submit">Обновить</button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Удалить запись?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $tank['id'] ?>">
                                        <button type="submit" style="background:#ef4444">Удалить</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

