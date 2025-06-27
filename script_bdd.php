<?php

$pdo = new PDO('mysql:host=127.0.0.1;port=8889;dbname=ProjetSymfony;charset=utf8mb4', 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ---------------- USERS ----------------
$roles = [1, 2, 3];
for ($i = 1; $i <= 20; $i++) {
    $pseudo = "user{$i}";
    $email = "user{$i}@example.com";
    $password = password_hash("pass{$i}", PASSWORD_BCRYPT);
    $role_id = $roles[array_rand($roles)];

    $stmt = $pdo->prepare("INSERT INTO user (id_role_id, pseudo, email, password, blocked) VALUES (?, ?, ?, ?, NULL)");
    $stmt->execute([$role_id, $pseudo, $email, $password]);
}

// ---------------- CATEGORIES & BOARDS ----------------
$categories = [
    'Développement Backend',
    'Frontend & UI/UX',
    'DevOps & Infrastructure',
    'Qualité & Testing',
    'Veille technologique & Carrière'
];

$category_ids = [];

foreach ($categories as $catName) {
    $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
    $stmt->execute([$catName]);
    $category_ids[] = $pdo->lastInsertId();
}

$boards = [];
foreach ($category_ids as $categoryId) {
    $numBoards = rand(6, 8);
    for ($i = 0; $i < $numBoards; $i++) {
        $boardName = "Board {$i} - " . uniqid();
        $stmt = $pdo->prepare("INSERT INTO board (name) VALUES (?)");
        $stmt->execute([$boardName]);
        $boardId = $pdo->lastInsertId();

        // board_category link
        $stmt = $pdo->prepare("INSERT INTO board_category (board_id, category_id) VALUES (?, ?)");
        $stmt->execute([$boardId, $categoryId]);

        $boards[] = $boardId;
    }
}

// ---------------- POSTS & COMMENTS ----------------
$post_titles = [
    "Comment structurer un projet Symfony ?",
    "Utiliser Doctrine avec des relations complexes",
    "Mise en cache HTTP vs cache applicatif",
    "Gérer les erreurs 500 en production",
    "Routing dynamique dans Symfony",
    "Configuration d’un reverse proxy Nginx",
    "Intégrer React avec Twig",
    "Les nouveautés de PHP 8.3"
];

$comment_templates = [
    "Bonne question, j’ai eu le même souci récemment.",
    "Tu peux aussi regarder du côté de {tech}.",
    "Je recommande d’utiliser {tech}, plus simple.",
    "Ça dépend du contexte, mais ça fonctionne bien avec {tech}.",
    "Merci pour le partage, très utile."
];

foreach ($boards as $boardId) {
    for ($i = 0; $i < 5; $i++) {
        $userId = rand(1, 20);
        $title = $post_titles[array_rand($post_titles)];
        $content = "Contenu du post : " . $title;
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("INSERT INTO post (user_id, board_id, title, content, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $boardId, $title, $content, $createdAt]);

        $postId = $pdo->lastInsertId();

        $numComments = rand(0, 5);
        for ($j = 0; $j < $numComments; $j++) {
            $commentUserId = rand(1, 20);
            $template = $comment_templates[array_rand($comment_templates)];
            $tech = ['API Platform', 'Monolog', 'Doctrine', 'Symfony CLI', 'PostgreSQL'][rand(0, 4)];
            $comment = str_replace('{tech}', $tech, $template);
            $commentDate = date('Y-m-d H:i:s', strtotime("+{$j} minutes"));

            $stmt = $pdo->prepare("INSERT INTO comment (user_id, content, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$commentUserId, $comment, $commentDate]);

            $commentId = $pdo->lastInsertId();

            // Optional: simulate a file upload
            if (rand(0, 1) === 1) {
                $fileNameOriginal = "capture_{$j}.png";
                $fileNameHashed = md5($fileNameOriginal . microtime()) . ".png";

                $stmt = $pdo->prepare("INSERT INTO file (comment_id, name_hashed, name_original) VALUES (?, ?, ?)");
                $stmt->execute([$commentId, $fileNameHashed, $fileNameOriginal]);
            }
        }
    }
}

echo "Données générées avec succès ✅\n";
