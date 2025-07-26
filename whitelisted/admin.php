<?php
session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit('Access Denied');
}
$questions = [
    ["question" => "Mi a teendő, ha egy adminisztrátorral beszélsz?", "options" => ["A" => "Figyelmen kívül hagyom.", "B" => "Tisztelettudóan és érthetően kommunikálok.", "C" => "Vitatkozok vele."], "answer" => "B"],
    ["question" => "Mi a 'VDM' (Vehicle Deathmatch) jelentése?", "options" => ["A" => "Barátságos dudálás.", "B" => "Egy másik játékos szándékos elütése járművel, megfelelő indok nélkül.", "C" => "Gyorshajtás a városban."], "answer" => "B"],
    ["question" => "Szabad-e más játékosokat szidni vagy zaklatni?", "options" => ["A" => "Igen, ha vicces.", "B" => "Nem, a tisztelet a legfontosabb.", "C" => "Csak akkor, ha ők kezdték."], "answer" => "B"]
];

$pending_file = 'pending.json';
$whitelist_file = 'whitelist.txt';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'];

    if ($action === 'approve' || $action === 'reject') {
        $pending_list = file_exists($pending_file) ? json_decode(file_get_contents($pending_file), true) ?: [] : [];
        $updated_list = [];
        $user_found = false;
        foreach ($pending_list as $app) {
            if ($app['id'] === $user_id) {
                $user_found = true;
                if ($action === 'approve') {
                    $whitelist = file_exists($whitelist_file) ? file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
                    if (!in_array($user_id, $whitelist)) {
                        file_put_contents($whitelist_file, $user_id . PHP_EOL, FILE_APPEND | LOCK_EX);
                    }
                }
            } else {
                $updated_list[] = $app;
            }
        }
        if ($user_found) {
            file_put_contents($pending_file, json_encode($updated_list, JSON_PRETTY_PRINT));
        }
    }

    if ($action === 'remove_whitelist') {
        if (file_exists($whitelist_file)) {
            $whitelist = file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $new_whitelist = array_filter($whitelist, function($id) use ($user_id) {
                return $id !== $user_id;
            });
            file_put_contents($whitelist_file, implode(PHP_EOL, $new_whitelist) . PHP_EOL);
        }
    }

    header('Location: admin.php');
    exit();
}

$pending_applications = file_exists($pending_file) ? json_decode(file_get_contents($pending_file), true) ?: [] : [];
$pending_applications = array_reverse($pending_applications); 
$approved_users = file_exists($whitelist_file) ? file($whitelist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Felület - Whitelist Kezelő</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { -webkit-font-smoothing: antialiased; background-color: #111827; }
        .glass-card { background-color: rgba(31, 41, 55, 0.5); backdrop-filter: blur(16px); border: 1px solid rgba(55, 65, 81, 0.5); }
    </style>
</head>
<body class="text-gray-200 font-sans">
    <div class="container mx-auto p-4 md:p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-black text-white">Admin Felület</h1>
                <p class="text-lime-400">Whitelist Kezelő</p>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition">
                <i class="ph-arrow-left"></i> Vissza
            </a>
        </div>

        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-4">Függőben lévő kérelmek (<?= count($pending_applications) ?>)</h2>
            <div class="space-y-8">
                <?php if (empty($pending_applications)): ?>
                    <div class="glass-card rounded-lg p-16 text-center text-gray-400">
                        <i class="ph-fill ph-check-circle text-6xl mb-4"></i>
                        <h3 class="text-2xl text-white">Minden naprakész!</h3><p>Nincsenek függőben lévő kérelmek.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_applications as $app): ?>
                        <div class="glass-card rounded-lg shadow-lg overflow-hidden">
                            <div class="p-4 bg-gray-800/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <h3 class="font-bold text-xl text-white"><?= htmlspecialchars($app['username']) ?></h3>
                                    <p class="text-sm text-gray-400 font-mono"><?= htmlspecialchars($app['id']) ?></p>
                                    <p class="text-sm text-gray-400">Beérkezett: <?= date('Y-m-d H:i', $app['timestamp']) ?></p>
                                </div>
                                <div class="flex flex-shrink-0 self-start md:self-center gap-2">
                                    <a href="?action=approve&id=<?= $app['id'] ?>" class="inline-flex items-center justify-center gap-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-3 rounded-md transition" onclick="return confirm('Biztosan elfogadod ezt a kérelmet?')"><i class="ph-check"></i> Elfogad</a>
                                    <a href="?action=reject&id=<?= $app['id'] ?>" class="inline-flex items-center justify-center gap-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-md transition" onclick="return confirm('Biztosan elutasítod ezt a kérelmet?')"><i class="ph-x"></i> Elutasít</a>
                                </div>
                            </div>
                            <div class="p-4 space-y-4">
                                <?php foreach ($questions as $index => $q_data): ?>
                                    <?php $user_answer_key = $app['answers'][$index] ?? null; $correct_answer_key = $q_data['answer']; ?>
                                    <div class="border border-gray-700 rounded-md p-4">
                                        <p class="font-semibold text-white mb-3"><?= ($index + 1) ?>. Kérdés: <?= htmlspecialchars($q_data['question']) ?></p>
                                        <div class="space-y-2">
                                            <?php foreach ($q_data['options'] as $option_key => $option_text): ?>
                                                <?php
                                                    $style_class = "bg-gray-700/50"; $icon = '<i class="ph-circle text-sm opacity-20"></i>';
                                                    if ($option_key === $user_answer_key) {
                                                        if ($user_answer_key === $correct_answer_key) { $style_class = "bg-green-800/50 border-green-600"; $icon = '<i class="ph-check-circle text-green-400"></i>'; }
                                                        else { $style_class = "bg-red-800/50 border-red-600"; $icon = '<i class="ph-x-circle text-red-400"></i>'; }
                                                    } elseif ($option_key === $correct_answer_key) { $style_class = "bg-sky-800/40 border-sky-600/50"; $icon = '<i class="ph-info text-sky-400"></i>'; }
                                                ?>
                                                <div class="flex items-center gap-3 p-2 rounded-md border border-transparent <?= $style_class ?>">
                                                    <?= $icon ?><span><?= htmlspecialchars($option_text) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-white mb-4">Jóváhagyott Felhasználók (<?= count($approved_users) ?>)</h2>
            <div class="glass-card rounded-lg shadow-lg overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-gray-800/50">
                        <tr>
                            <th class="p-4">Discord ID</th>
                            <th class="p-4 text-right">Művelet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approved_users)): ?>
                            <tr><td colspan="2" class="p-8 text-center text-gray-400">A whitelist jelenleg üres.</td></tr>
                        <?php else: ?>
                            <?php foreach ($approved_users as $user_id): ?>
                                <tr class="border-t border-gray-700">
                                    <td class="p-4 font-mono text-white"><?= htmlspecialchars($user_id) ?></td>
                                    <td class="p-4 text-right">
                                        <a href="?action=remove_whitelist&id=<?= $user_id ?>" class="inline-flex items-center justify-center gap-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-3 rounded-md transition text-sm" onclick="return confirm('Biztosan törlöd ezt a felhasználót a whitelistről?')">
                                            <i class="ph-trash"></i> Törlés
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>