<?php
session_start();

define('DISCORD_CLIENT_ID', 'YOUR_DISCORD_CLIENT_ID_HERE');
define('DISCORD_CLIENT_SECRET', 'YOUR_DISCORD_CLIENT_SECRET_HERE');
define('DISCORD_REDIRECT_URI', 'YOUR_DOMAIN_REDIRECT_URI');
define('DISCORD_SERVER_INVITE', 'YOUR_DISCORD_INVITE_LINK');

$admin_discord_ids = [
    'YOUR_ADMIN_DISCORD_ID',
];

$server_name = "YOUR_SERVER_NAME";
$server_ip = "YOUR_SERVER_IP";

function getUserStatus($userId)
{
    if (file_exists('whitelist.txt')) {
        $whitelist = file('whitelist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($userId, $whitelist)) {
            return 'approved';
        }
    }
    if (file_exists('pending.json')) {
        $pending_list = json_decode(file_get_contents('pending.json'), true) ?: [];
        foreach ($pending_list as $app) {
            if ($app['id'] === $userId) {
                return 'pending';
            }
        }
    }
    return 'new';
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

if (isset($_GET['code']) && !isset($_SESSION['discord_user'])) {
    $token_url = "https://discord.com/api/oauth2/token";
    $token_data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => DISCORD_REDIRECT_URI
    ];

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($token_response['access_token'])) {
        $user_url = "https://discord.com/api/users/@me";
        $auth_header = "Authorization: Bearer " . $token_response['access_token'];
        $ch = curl_init($user_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$auth_header, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_data = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $_SESSION['discord_user'] = [
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'avatar' => 'https://cdn.discordapp.com/avatars/' . $user_data['id'] . '/' . $user_data['avatar'] . '.png'
        ];

        $cleaned_admin_ids = array_map('trim', $admin_discord_ids);
        $_SESSION['is_admin'] = in_array($user_data['id'], $cleaned_admin_ids);
    }

    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

$is_logged_in = isset($_SESSION['discord_user']);
$user_status = $is_logged_in ? getUserStatus($_SESSION['discord_user']['id']) : 'new';
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$discord_auth_url = 'https://discord.com/api/oauth2/authorize?client_id=' . DISCORD_CLIENT_ID . '&redirect_uri=' . urlencode(DISCORD_REDIRECT_URI) . '&response_type=code&scope=identify';
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $server_name ?> Whitelist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes aurora {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeOutPage {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        .page-fade-out {
            animation: fadeOutPage 0.5s ease-in forwards;
        }

        body {
            -webkit-font-smoothing: antialiased;
            background-color: #111827;
            color: #d1d5db;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .aurora-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
            background-color: #111827;
        }

        .aurora-bg::before,
        .aurora-bg::after {
            content: '';
            position: absolute;
            width: 80vmax;
            height: 80vmax;
            border-radius: 50%;
            mix-blend-mode: overlay;
            filter: blur(90px);
            opacity: 0.2;
            animation: aurora 25s linear infinite;
        }

        .aurora-bg::before {
            background: radial-gradient(circle, #84cc16, transparent 60%);
            top: -40%;
            left: -40%;
        }

        .aurora-bg::after {
            background: radial-gradient(circle, #22d3ee, transparent 60%);
            bottom: -40%;
            right: -40%;
            animation-direction: reverse;
        }

        .glass-card {
            background-color: rgba(31, 41, 55, 0.5);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(55, 65, 81, 0.5);
        }

        .glow-avatar {
            box-shadow: 0 0 20px rgba(132, 204, 22, 0.4);
        }

        .btn-primary {
            background: linear-gradient(to right, #a3e635, #84cc16);
        }

        .btn-primary:hover {
            box-shadow: 0 0 25px rgba(163, 230, 53, 0.5);
        }
    </style>
</head>

<body class="font-sans">
    <div class="aurora-bg"></div>
    <div class="min-h-screen flex flex-col md:flex-row relative z-10">
        <div class="w-full md:w-2/5 lg:w-1/3 p-8 flex flex-col">
            <header class="mb-12">
                <h1 class="text-4xl lg:text-5xl font-black text-white"><?= $server_name ?></h1>
                <p class="text-lime-400 mt-1 text-lg">Hivatalos Whitelist Rendszer</p>
            </header>
            <main class="space-y-10">
                <div>
                    <h3 class="font-bold text-white text-xl mb-4">Hogyan működik?</h3>
                    <ol class="relative border-l border-gray-700 space-y-6">
                        <li class="ml-6"><span
                                class="absolute flex items-center justify-center w-6 h-6 bg-gray-700 rounded-full -left-3 ring-4 ring-gray-800"><i
                                    class="ph-fill ph-discord-logo"></i></span>
                            <h4 class="font-semibold text-white">1. Bejelentkezés</h4>
                            <p class="text-sm text-gray-400">Kattints a bejelentkezés gombra és hitelesítsd magad a
                                Discord fiókoddal.</p>
                        </li>
                        <li class="ml-6"><span
                                class="absolute flex items-center justify-center w-6 h-6 bg-gray-700 rounded-full -left-3 ring-4 ring-gray-800"><i
                                    class="ph-fill ph-scroll"></i></span>
                            <h4 class="font-semibold text-white">2. Kvíz Kitöltése</h4>
                            <p class="text-sm text-gray-400">Válaszolj a szabályzattal kapcsolatos kérdésekre a tudásod
                                bizonyításához.</p>
                        </li>
                        <li class="ml-6"><span
                                class="absolute flex items-center justify-center w-6 h-6 bg-gray-700 rounded-full -left-3 ring-4 ring-gray-800"><i
                                    class="ph-fill ph-user-gear"></i></span>
                            <h4 class="font-semibold text-white">3. Adminisztrátori Elbírálás</h4>
                            <p class="text-sm text-gray-400">Várj türelemmel, amíg egy adminisztrátor ellenőrzi és
                                jóváhagyja a kérelmedet.</p>
                        </li>
                    </ol>
                </div>
                <div>
                    <h3 class="font-bold text-white text-xl mb-4">Csatlakozz a közösséghez!</h3>
                    <a href="<?= DISCORD_SERVER_INVITE ?>" target="_blank"
                        class="w-full inline-flex items-center justify-center gap-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition-all">
                        <i class="ph-fill ph-discord-logo text-2xl text-lime-400"></i> Csatlakozás a Discordra
                    </a>
                </div>
            </main>
            <footer class="mt-auto pt-8">
                <?php if ($is_logged_in): ?>
                    <div class="mt-8 md:mt-0">
                        <div class="flex items-center gap-4 mt-2">
                            <img src="<?= $_SESSION['discord_user']['avatar'] ?>" alt="Avatar"
                                class="w-12 h-12 rounded-full glow-avatar">
                            <div>
                                <p class="font-bold text-white text-lg">
                                    <?= htmlspecialchars($_SESSION['discord_user']['username']) ?></p>
                                <a href="?action=logout" id="logout-link"
                                    class="text-sm text-gray-400 hover:text-red-400 transition">Kijelentkezés <i
                                        class="ph-sign-out align-middle"></i></a>
                            </div>
                            <?php if ($is_admin): ?>
                                <a href="admin.php"
                                    class="ml-auto p-2 text-gray-400 hover:text-white transition rounded-full hover:bg-white/10"
                                    title="Admin Felület"><i class="ph-user-gear text-2xl"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </footer>
        </div>

        <div class="w-full md:w-3/5 lg:w-2/3 p-8 flex items-center justify-center bg-gray-900/50">
            <div class="w-full max-w-2xl text-center fade-in">
                <?php if (!$is_logged_in): ?>
                    <i class="ph-fill ph-keyhole text-8xl text-lime-400"></i>
                    <h2 class="text-4xl font-bold text-white mt-4">Kezdődjön a kaland!</h2>
                    <p class="text-gray-400 mt-2 mb-8">A belépéshez jelentkezz be a Discord fiókoddal.</p>
                    <a href="<?= $discord_auth_url ?>"
                        class="inline-flex items-center gap-3 btn-primary text-gray-900 font-bold py-3 px-8 rounded-lg text-lg transition-all transform hover:scale-105">
                        <i class="ph-fill ph-discord-logo text-2xl"></i> Bejelentkezés
                    </a>
                <?php elseif ($user_status == 'approved'): ?>
                    <i class="ph-fill ph-check-circle text-8xl text-green-400"></i>
                    <h2 class="text-3xl font-bold text-white mt-4">Kérelmed elfogadva!</h2>
                    <p class="text-gray-300 mt-2 mb-8">Sikeresen felkerültél a whitelistre. Jó játékot kívánunk!</p>
                    <a href="fivem://connect/<?= $server_ip ?>"
                        class="inline-flex items-center gap-3 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg text-lg transition-all transform hover:scale-105">
                        <i class="ph-fill ph-game-controller text-2xl"></i> Csatlakozás
                    </a>
                <?php elseif ($user_status == 'pending'): ?>
                    <i class="ph-fill ph-clock-countdown text-8xl text-amber-400"></i>
                    <h2 class="text-3xl font-bold text-white mt-4">Kérelmed elbírálás alatt</h2>
                    <p class="text-gray-300 mt-2 mb-8">Egy adminisztrátor hamarosan ellenőrzi a kérelmedet. Addig is
                        csatlakozz a Discord szerverünkhöz! Ha szeretnéd, a tesztet újra kitöltheted.</p>
                    <a href="quiz.php"
                        class="inline-flex items-center gap-3 btn-primary text-gray-900 font-bold py-3 px-8 rounded-lg text-lg transition-all transform hover:scale-105">
                        <i class="ph-fill ph-arrow-counter-clockwise text-2xl"></i> Teszt újraküldése
                    </a>
                <?php else: // 'new' status ?>
                    <i class="ph-fill ph-file-text text-8xl text-lime-400"></i>
                    <h2 class="text-3xl font-bold text-white mt-4">Már csak egy lépés!</h2>
                    <p class="text-gray-300 mt-2 mb-8">Töltsd ki a rövid szabályzattesztet, hogy hozzáférést kapj a
                        szerverhez.</p>
                    <a href="quiz.php"
                        class="inline-flex items-center gap-3 btn-primary text-gray-900 font-bold py-3 px-8 rounded-lg text-lg transition-all transform hover:scale-105">
                        <i class="ph-fill ph-caret-right text-2xl"></i> Teszt Elkezdése
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoutLink = document.getElementById('logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function (event) {
                    event.preventDefault();
                    document.body.classList.add('page-fade-out');
                    setTimeout(() => { window.location.href = this.href; }, 500);
                });
            }
        });
    </script>
</body>

</html>