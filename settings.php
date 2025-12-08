<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$note = "";

/* SAVE SETTINGS*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_settings'])) {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $currency  = trim($_POST['currency'] ?? 'PHP');
    $timezone  = trim($_POST['timezone'] ?? 'Asia/Manila');
    $theme     = $_POST['theme'] ?? 'light';

    $notify_budget_alerts         = isset($_POST['notify_budget_alerts']) ? 1 : 0;
    $notify_transaction_reminders = isset($_POST['notify_transaction_reminders']) ? 1 : 0;
    $notify_monthly_reports       = isset($_POST['notify_monthly_reports']) ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $note = "<div class='msg error'>Please enter a valid email address.</div>";
    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET full_name = ?, email = ?, currency = ?, timezone = ?, theme = ?,
                notify_budget_alerts = ?, notify_transaction_reminders = ?, notify_monthly_reports = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssiii",
            $full_name,
            $email,
            $currency,
            $timezone,
            $theme,
            $notify_budget_alerts,
            $notify_transaction_reminders,
            $notify_monthly_reports,
            $user_id
        );

        $stmt->execute();
        $stmt->close();

        $_SESSION['theme'] = $theme;
        $note = "<div class='msg success'>Settings saved successfully.</div>";
    }
}

/*  LOAD CURRENT SETTINGS */
$stmt = $conn->prepare("
    SELECT username, full_name, email, currency, timezone, theme,
           notify_budget_alerts, notify_transaction_reminders, notify_monthly_reports
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result   = $stmt->get_result();
$settings = $result->fetch_assoc();
$stmt->close();

$full_name  = $settings['full_name'] ?: $settings['username'];
$email      = $settings['email'];
$currency   = $settings['currency'] ?: 'PHP';
$timezone   = $settings['timezone'] ?: 'Asia/Manila';
$theme      = $settings['theme'] ?: 'light';
$_SESSION['theme'] = $theme;

$notify_budget_alerts         = (int)$settings['notify_budget_alerts'];
$notify_transaction_reminders = (int)$settings['notify_transaction_reminders'];
$notify_monthly_reports       = (int)$settings['notify_monthly_reports'];

/*FULL CURRENCY LIST*/
$currencyOptions = [
    'AED'=>'United Arab Emirates Dirham','AFN'=>'Afghan Afghani','ALL'=>'Albanian Lek','AMD'=>'Armenian Dram',
    'ANG'=>'Netherlands Antillean Guilder','AOA'=>'Angolan Kwanza','ARS'=>'Argentine Peso','AUD'=>'Australian Dollar',
    'AWG'=>'Aruban Florin','AZN'=>'Azerbaijani Manat','BAM'=>'Bosnia Convertible Mark','BBD'=>'Barbadian Dollar',
    'BDT'=>'Bangladeshi Taka','BGN'=>'Bulgarian Lev','BHD'=>'Bahraini Dinar','BMD'=>'Bermudian Dollar',
    'BND'=>'Brunei Dollar','BOB'=>'Boliviano','BRL'=>'Brazilian Real','BSD'=>'Bahamian Dollar','BWP'=>'Botswana Pula',
    'BYN'=>'Belarusian Ruble','BZD'=>'Belize Dollar','CAD'=>'Canadian Dollar','CHF'=>'Swiss Franc','CLP'=>'Chilean Peso',
    'CNY'=>'Chinese Yuan','COP'=>'Colombian Peso','CRC'=>'Costa Rican Colón','CZK'=>'Czech Koruna','DKK'=>'Danish Krone',
    'DOP'=>'Dominican Peso','DZD'=>'Algerian Dinar','EGP'=>'Egyptian Pound','EUR'=>'Euro','GBP'=>'British Pound Sterling',
    'HKD'=>'Hong Kong Dollar','HUF'=>'Hungarian Forint','IDR'=>'Indonesian Rupiah','ILS'=>'Israeli Shekel',
    'INR'=>'Indian Rupee','JPY'=>'Japanese Yen','KRW'=>'South Korean Won','KWD'=>'Kuwaiti Dinar','MXN'=>'Mexican Peso',
    'MYR'=>'Malaysian Ringgit','NOK'=>'Norwegian Krone','NZD'=>'New Zealand Dollar','PHP'=>'Philippine Peso',
    'PKR'=>'Pakistani Rupee','PLN'=>'Polish Zloty','QAR'=>'Qatari Riyal','RUB'=>'Russian Ruble','SAR'=>'Saudi Riyal',
    'SEK'=>'Swedish Krona','SGD'=>'Singapore Dollar','THB'=>'Thai Baht','TRY'=>'Turkish Lira',
    'USD'=>'US Dollar','VND'=>'Vietnamese Dong','ZAR'=>'South African Rand'
];

/*FULL TIMEZONE LIST*/
$timezoneOptions = [];
foreach (timezone_identifiers_list() as $tz) {
    $offset  = (new DateTimeZone($tz))->getOffset(new DateTime("now", new DateTimeZone($tz)));
    $hours   = floor($offset / 3600);
    $minutes = abs(($offset % 3600) / 60);
    $sign    = $hours >= 0 ? '+' : '-';
    $formatted = sprintf("GMT%s%02d:%02d", $sign, abs($hours), $minutes);
    $timezoneOptions[$tz] = "$tz ($formatted)";
}

/* THEME OPTIONS */
$themeOptions = [
    'light'  => 'Light',
    'dark'   => 'Dark',
    'system' => 'Auto (System)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings • TrackSmart</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="assets/css/style.css?v=30">

<style>
body { background:#f7f8fb; font-family:Arial; margin:0; padding:0; }
.main-content { padding:20px; }
.settings-wrapper { max-width:800px; margin:auto; }

/* CARD */
.settings-card {
    background:#fff;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}

/* CARD HEADERS */
.settings-card-title {
    font-size:18px;
    font-weight:400;
    margin-bottom:15px;
    display:flex;
    align-items:center;
    gap:10px;
    color:#000;
}

.settings-card-title i {
    color:#391054; /* ICON COLOR */
    font-size:20px;
}

/* INPUT */
.settings-label { font-weight:500; margin-bottom:6px; }
.input, select {
    width:100%;
    padding:10px 14px;
    border-radius:8px;
    border:1px solid #ddd;
    margin-bottom:15px;
    font-size:14px;
}

/* CHECKBOX */
.settings-checkbox {
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
}

/* BUTTONS */
.save-btn {
    background:#391054; /* MATCH ICON */
    color:white;
    padding:12px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
}

.cancel-btn {
    background:#f1f1f1;
    color:#333;
    padding:12px 20px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.settings-actions {
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

/* DARK MODE */
body.dark {
    background:#121212 !important;
    color:#f1f1f1 !important;
}

body.dark .settings-card,
body.dark .input,
body.dark select,
body.dark .choices__inner {
    background:#1e1e1e !important;
    color:#fff !important;
    border-color:#333 !important;
}

body.dark .settings-card-title,
body.dark .settings-card-title i {
    color:#c9b3ff !important;
}

body.dark .save-btn {
    background:#391054 !important;
}

body.dark .cancel-btn {
    background:#333 !important;
    color:#fff;
}
</style>
</head>

<body class="<?= htmlspecialchars($theme) ?>">

<?php include 'sidebar.php'; ?>

<div class="main-content">
<div class="settings-wrapper">

    <h1>Settings</h1>
    <?= $note ?>

    <form method="POST">

        <!-- PROFILE -->
        <div class="settings-card">
            <h2 class="settings-card-title"><i class="fa fa-user"></i> Profile Settings</h2>

            <label class="settings-label">Full Name</label>
            <input type="text" name="full_name" class="input" value="<?= htmlspecialchars($full_name) ?>" required>

            <label class="settings-label">Email</label>
            <input type="email" name="email" class="input" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <!-- REGIONAL -->
        <div class="settings-card">
            <h2 class="settings-card-title"><i class="fa fa-globe"></i> Regional Settings</h2>

            <label class="settings-label">Currency</label>
            <select id="currency" name="currency" class="input">
                <?php foreach ($currencyOptions as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $code === $currency ? 'selected' : '' ?>>
                        <?= $code ?> - <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="settings-label">Timezone</label>
            <select id="timezone" name="timezone" class="input">
                <?php foreach ($timezoneOptions as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $code === $timezone ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- APPEARANCE -->
        <div class="settings-card">
            <h2 class="settings-card-title"><i class="fa fa-paint-brush"></i> Appearance</h2>

            <label class="settings-label">Theme</label>
            <select name="theme" class="input">
                <?php foreach ($themeOptions as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $code === $theme ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- NOTIFICATIONS -->
        <div class="settings-card">
            <h2 class="settings-card-title"><i class="fa fa-bell"></i> Notifications</h2>

            <label class="settings-checkbox">
                <input type="checkbox" name="notify_budget_alerts" value="1" <?= $notify_budget_alerts ? 'checked' : '' ?>>
                Budget alerts
            </label>

            <label class="settings-checkbox">
                <input type="checkbox" name="notify_transaction_reminders" value="1" <?= $notify_transaction_reminders ? 'checked' : '' ?>>
                Transaction reminders
            </label>

            <label class="settings-checkbox">
                <input type="checkbox" name="notify_monthly_reports" value="1" <?= $notify_monthly_reports ? 'checked' : '' ?>>
                Monthly reports
            </label>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="settings-actions">
            <button type="button" class="cancel-btn" onclick="window.location='index.php'">Cancel</button>
            <button type="submit" name="save_settings" class="save-btn">Save Changes</button>
        </div>

    </form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
new Choices('#currency', { searchEnabled:true, itemSelectText:'', shouldSort:false });
new Choices('#timezone', { searchEnabled:true, itemSelectText:'', shouldSort:false });
</script>

</body>
</html>




