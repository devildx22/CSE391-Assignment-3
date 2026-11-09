<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $new_date = trim($_POST['new_date']);
    $new_time = trim($_POST['new_time']);
    $new_mechanic = (int)$_POST['new_mechanic'];

    try {
        $stmtCapacity = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE mechanic_id = ? AND appointment_date = ? AND id != ?");
        $stmtCapacity->execute([$new_mechanic, $new_date, $appointment_id]);
        
        if ($stmtCapacity->fetchColumn() >= 4) {
            $error_msg = "Cannot reassign: The selected mechanic already has 4 cars on that date.";
        } else {
            $updateStmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, time_slot = ?, mechanic_id = ? WHERE id = ?");
            $updateStmt->execute([$new_date, $new_time, $new_mechanic, $appointment_id]);
            $success_msg = "Appointment successfully updated.";
        }
    } catch (PDOException $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    }
}

$appointments = [];
$mechanics = [];
try {
    $stmt = $pdo->query("SELECT a.id, a.client_name, a.client_phone, a.car_license_number, a.appointment_date, a.time_slot, a.mechanic_id, m.name as mechanic_name 
                         FROM appointments a JOIN mechanics m ON a.mechanic_id = m.id ORDER BY a.appointment_date DESC");
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mechStmt = $pdo->query("SELECT id, name FROM mechanics");
    $mechanics = $mechStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching data.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | RF - Autos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(rgba(12, 14, 18, 0.9), rgba(12, 14, 18, 0.9)), url('bg-garage.jpg') no-repeat center center fixed; background-size: cover; color: #e2e8f0; padding: 40px; margin: 0; min-height: 100vh; }
        .admin-container { max-width: 1100px; margin: 0 auto; background: rgba(22, 26, 34, 0.95); backdrop-filter: blur(10px); padding: 30px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 15px 35px rgba(0,0,0,0.8); }
        h2 { color: #10b981; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-top: 0; }
        .help-box { background: rgba(30, 41, 59, 0.8); padding: 15px; border-left: 4px solid #3b82f6; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .help-box strong { color: #60a5fa; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: rgba(15, 23, 42, 0.8); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #334155; font-size: 14px; }
        th { background: #1e293b; color: #94a3b8; text-transform: uppercase; }
        .edit-form { display: flex; gap: 8px; align-items: center; }
        input[type="date"], select { padding: 6px; background: #161a22; border: 1px solid #334155; color: white; border-radius: 4px; }
        button { background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2563eb; }
        .msg { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .msg.success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; }
        .msg.error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; }
    </style>
</head>
<body>
<div class="admin-container">
    <h2>Workshop Administration Panel</h2>
    <?php if(!empty($success_msg)) echo "<div class='msg success'>$success_msg</div>"; ?>
    <?php if(!empty($error_msg)) echo "<div class='msg error'>$error_msg</div>"; ?>
    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Car Reg No.</th>
                <th>Current Date & Time</th>
                <th>Current Mechanic</th>
                <th>Reassign (Date, Time, Mechanic)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td><?php echo htmlspecialchars($row['car_license_number']); ?></td>
                <td><?php echo htmlspecialchars($row['appointment_date']) . ' <br><span style="color:#10b981;">' . htmlspecialchars($row['time_slot']) . '</span>'; ?></td>
                <td><?php echo htmlspecialchars($row['mechanic_name']); ?></td>
                <td>
                    <form method="POST" class="edit-form">
                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                        <input type="date" name="new_date" value="<?php echo $row['appointment_date']; ?>" required>
                        <select name="new_time" required>
                            <option value="Morning" <?php if($row['time_slot'] == 'Morning') echo 'selected'; ?>>Morning</option>
                            <option value="Evening" <?php if($row['time_slot'] == 'Evening') echo 'selected'; ?>>Evening</option>
                            <option value="Night" <?php if($row['time_slot'] == 'Night') echo 'selected'; ?>>Night</option>
                        </select>
                        <select name="new_mechanic" required>
                            <?php foreach ($mechanics as $mech): ?>
                                <option value="<?php echo $mech['id']; ?>" <?php if($mech['id'] == $row['mechanic_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($mech['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_appointment">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>