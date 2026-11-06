<?php
require_once __DIR__ . '/../db/init.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT id, title, slug, status, created_at FROM blog_posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<h2>Erreur SQL</h2><pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Debug Blog Posts</title>
    <style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}th{background:#f4f4f4}</style>
</head>
<body>
<h1>Debug: blog_posts</h1>
<p>Base: <?php echo htmlspecialchars(getenv('DB_NAME') ?: 'zoetic', ENT_QUOTES, 'UTF-8'); ?></p>
<?php if (empty($posts)) : ?>
    <p>Aucun article trouvé dans la table <strong>blog_posts</strong>.</p>
<?php else : ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th><th>Created At</th></tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($p['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>Si les articles existent mais ont le statut <code>draft</code>, publiez-les via l'admin ou exécutez en SQL :</p>
<pre>UPDATE blog_posts SET status = 'published' WHERE id = 1;</pre>

</body>
</html>
