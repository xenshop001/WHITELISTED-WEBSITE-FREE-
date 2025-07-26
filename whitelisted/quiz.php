<?php
session_start();

if (!isset($_SESSION['discord_user'])) {
    header('Location: index.php');
    exit();
}

function getUserStatus($userId) { if (file_exists('whitelist.txt')) { $whitelist = file('whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); if (in_array($userId, $whitelist)) { return 'approved'; } } if (file_exists('pending.json')) { $pending_list = json_decode(file_get_contents('pending.json'), true) ?: []; foreach ($pending_list as $app) { if ($app['id'] === $userId) { return 'pending'; } } } return 'new'; }
$user_status = getUserStatus($_SESSION['discord_user']['id']);
if ($user_status === 'approved') {
    header('Location: index.php');
    exit();
}


$passing_percentage = 70;
$questions = [
    ["question" => "Mi a teendő, ha egy adminisztrátorral beszélsz?", "options" => ["A" => "Figyelmen kívül hagyom.", "B" => "Tisztelettudóan és érthetően kommunikálok.", "C" => "Vitatkozok vele."], "answer" => "B"],
    ["question" => "Mi a 'VDM' (Vehicle Deathmatch) jelentése?", "options" => ["A" => "Barátságos dudálás.", "B" => "Egy másik játékos szándékos elütése járművel, megfelelő indok nélkül.", "C" => "Gyorshajtás a városban."], "answer" => "B"],
    ["question" => "Szabad-e más játékosokat szidni vagy zaklatni?", "options" => ["A" => "Igen, ha vicces.", "B" => "Nem, a tisztelet a legfontosabb.", "C" => "Csak akkor, ha ők kezdték."], "answer" => "B"]
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $user = $_SESSION['discord_user'];
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    foreach ($questions as $index => $q) {
        if (isset($answers[$index]) && $answers[$index] == $q['answer']) $score++;
    }
    $percentage = (count($questions) > 0) ? ($score / count($questions)) * 100 : 0;

    if ($percentage >= $passing_percentage) {
        $pending_list = file_exists('pending.json') ? json_decode(file_get_contents('pending.json'), true) ?: [] : [];
        $user_index = -1;
        foreach ($pending_list as $index => $app) {
            if ($app['id'] === $user['id']) {
                $user_index = $index;
                break;
            }
        }
        $new_application = ['id' => $user['id'], 'username' => $user['username'], 'timestamp' => time(), 'answers' => $answers];
        if ($user_index !== -1) {
            $pending_list[$user_index] = $new_application;
        } else {
            $pending_list[] = $new_application;
        }
        file_put_contents('pending.json', json_encode($pending_list, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'message' => 'Sikeres vizsga! Kérelmedet továbbítottuk az adminisztrátoroknak.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sajnos nem sikerült. Elért pontszám: ' . round($percentage) . '%']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whitelist Teszt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        body { -webkit-font-smoothing: antialiased; background-color: #111827; color: #d1d5db; }
        .glass-card { background-color: rgba(31, 41, 55, 0.5); backdrop-filter: blur(16px); border: 1px solid rgba(55, 65, 81, 0.5); }
        .btn-primary { background: linear-gradient(to right, #a3e635, #84cc16); }
        .btn-primary:hover { box-shadow: 0 0 25px rgba(163, 230, 53, 0.5); }
    </style>
</head>
<body class="font-sans flex items-center justify-center min-h-screen p-4 bg-gray-900">
    <div class="w-full max-w-3xl fade-in">
        <div class="text-center mb-8">
            <i class="ph-fill ph-scroll text-8xl text-lime-400"></i>
            <h1 class="text-4xl font-bold text-white mt-4">Whitelist Teszt</h1>
            <p class="text-gray-400 mt-2">Válaszolj a kérdésekre a legjobb tudásod szerint. Minimum <?= $passing_percentage ?>% szükséges a sikeres beküldéshez.</p>
        </div>

        <?php if ($user_status == 'pending'): ?>
        <div class="glass-card border-amber-500/50 text-amber-300 p-4 rounded-lg mb-6 text-center flex items-center gap-3">
            <i class="ph-fill ph-warning-circle text-2xl"></i>
            <span>Figyelem! Már van egy függőben lévő kérelmed. Ha új tesztet küldesz be, az <strong>felülírja a korábbit.</strong></span>
        </div>
        <?php endif; ?>

        <div id="error-message-container" class="hidden glass-card border-red-500 text-red-300 p-4 rounded-lg mb-6 text-center"></div>
        
        <form id="quiz-form" class="space-y-4">
            <?php foreach ($questions as $index => $q_data): ?>
            <div class="glass-card p-5 rounded-xl">
                <p class="text-lg font-semibold mb-4 text-white"><i class="ph-fill ph-question mr-2 text-lime-400"></i><?= htmlspecialchars($q_data['question']) ?></p>
                <div class="space-y-2">
                    <?php foreach ($q_data['options'] as $key => $option): ?>
                    <label class="flex items-center p-3 glass-card border-transparent hover:border-lime-400/50 border rounded-lg cursor-pointer transition-all">
                        <input type="radio" name="answers[<?= $index ?>]" value="<?= $key ?>" class="h-4 w-4 text-lime-500 bg-slate-800 border-slate-600 focus:ring-lime-500 focus:ring-2" required>
                        <span class="ml-3 text-gray-300"><?= htmlspecialchars($option) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div>
                <button id="submit-button" type="submit" class="w-full mt-4 flex items-center justify-center gap-3 btn-primary text-gray-900 font-bold py-3 px-4 rounded-lg text-lg transition-all transform hover:scale-105 disabled:bg-gray-500 disabled:scale-100">
                    <span id="button-text">Teszt beküldése</span>
                    <i id="button-spinner" class="ph-spinner-gap animate-spin text-2xl hidden"></i>
                </button>
            </div>
        </form>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const quizForm = document.getElementById('quiz-form');
        const submitButton = document.getElementById('submit-button');
        quizForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            submitButton.disabled = true;
            document.getElementById('button-text').textContent = 'Értékelés...';
            document.getElementById('button-spinner').classList.remove('hidden');
            
            const formData = new FormData(quizForm);
            const response = await fetch('quiz.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                window.location.href = 'index.php';
            } else {
                const errorContainer = document.getElementById('error-message-container');
                errorContainer.textContent = result.message;
                errorContainer.classList.remove('hidden');
                submitButton.disabled = false;
                document.getElementById('button-text').textContent = 'Kvíz beküldése';
                document.getElementById('button-spinner').classList.add('hidden');
            }
        });
    });
</script>
</body>
</html>