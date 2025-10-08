<?php
session_start();


$correctPin = 1234;
$transactionResult = "";

if (isset($_POST['reset'])) {
    session_unset(); 
    session_destroy(); 
    session_start();   
    $_SESSION['balance'] = 5000; 
    $transactionResult = "<p style='color:blue;'>🔄 System has been reset. Dial *182# to start again.</p>";
}

if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 5000; }


$dialed = $_SESSION['dialed'] ?? false;

if (isset($_POST['dial'])) {
    $dialInput = $_POST['dialCode'] ?? '';
    if ($dialInput === '*182#') {
        $_SESSION['dialed'] = true;
        $dialed = true;
    } else {
        $transactionResult = "<p style='color:red;'>❌ Invalid code. Please dial *182# to start.</p>";
    }
}


$menuOption = $_POST['menuOption'] ?? null;  
$sendOption = $_POST['sendOption'] ?? null;  
$receiverNumber = $_POST['receiver'] ?? null;  
$amount = $_POST['amount'] ?? null;  
$enteredPin = $_POST['pin'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $dialed && !isset($_POST['reset']) && !isset($_POST['dial'])) {
    if ($menuOption == 1) { 
        if ($sendOption == 1) { 
            if (!empty($receiverNumber) && !empty($amount) && !empty($enteredPin)) {
                if ($amount > 0) {
                    if ($amount <= $_SESSION['balance']) {
                        if ($enteredPin == $correctPin) {
                            $_SESSION['balance'] -= $amount;
                            $time = date('Y-m-d H:i:s');
                            $transactionResult = "
                                <p style='color:green;'>✅ Transaction Successful!</p>
                                <p>You sent <b>{$amount} RWF</b> to <b>{$receiverNumber}</b>.</p>
                                <p>Remaining balance: <b>{$_SESSION['balance']} RWF</b></p>
                                <p><i>Transaction time: {$time}</i></p>
                            ";
                        } else {
                            $transactionResult = "<p style='color:red;'>❌ Invalid PIN. Transaction failed.</p>";
                        }
                    } else {
                        $transactionResult = "<p style='color:red;'>❌ Insufficient balance. Transaction failed.</p>";
                    }
                } else {
                    $transactionResult = "<p style='color:red;'>❌ Invalid amount. Must be greater than 0.</p>";
                }
            } else {
                $transactionResult = "<p style='color:red;'>❌ Please fill in all required fields.</p>";
            }
        } elseif ($sendOption == 2) {
            $transactionResult = "<p style='color:red;'>❌ Option not implemented for Other Networks.</p>";
        }
    } elseif ($menuOption == 2) {
        $transactionResult = "<p>Payments menu (not implemented)</p>";
    } elseif ($menuOption == 3) {
        $transactionResult = "<p>Airtime menu (not implemented)</p>";
    } else {
        $transactionResult = "<p style='color:red;'>❌ Invalid menu option.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AirPay</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #5a9aaaff; padding: 20px; }
        h2 { color: #edf41fff; text-align: center; }
        form { max-width: 400px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);}
        input[type="text"], input[type="number"], input[type="password"], select { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        input[type="submit"] { background-color: #e1f90c8b; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; }
        input[type="submit"]:hover { background-color: #dbee0dff; }
        .reset-btn { background-color: #f1fc1dff; margin-top: 10px; }
        .reset-btn:hover { background-color: #f3fa13ff; }
        p { font-weight: bold; }
        i { font-size: 14px; color: #c5ecefff; }
    </style>
</head>
<body>
    <h2>AirPay </h2>

    <?php if (!$dialed): ?>
    
        <form method="post">
            <label>Dial *182# to start:</label>
            <input type="text" name="dialCode" placeholder="Enter *182#" required>
            <input type="submit" name="dial" value="Dial">
        </form>
    <?php else: ?>
        
        <p style="text-align:center;">💰 Current Balance: <b><?php echo $_SESSION['balance']; ?> RWF</b></p>

        <form method="post">
            <input type="submit" name="reset" value="Reset System" class="reset-btn">
        </form>

        <form method="post">
            <label>Main Menu:</label>
            <select name="menuOption" required>
                <option value="">Select:</option>
                <option value="1" <?php if($menuOption==1) echo "selected"; ?>>
                    1. Send Money (Balance: <?php echo $_SESSION['balance']; ?> RWF)
                </option>
                <option value="2" <?php if($menuOption==2) echo "selected"; ?>>2. Payments</option>
                <option value="3" <?php if($menuOption==3) echo "selected"; ?>>3. Airtime</option>
            </select>
            <?php if($menuOption == 1): ?>
                <label>Send Money Options:</label>
                <select name="sendOption" required>
                    <option value="">Select:</option>
                    <option value="1" <?php if($sendOption==1) echo "selected"; ?>>1. MTN Number</option>
                    <option value="2" <?php if($sendOption==2) echo "selected"; ?>>2. Other Networks</option>
                </select>
            <?php endif; ?>

            <?php if($menuOption == 1 && $sendOption == 1): ?>
                <label>Receiver Number:</label>
                <input type="text" name="receiver" value="<?php echo $receiverNumber ?? ''; ?>" required>
                <label>Amount to Send (RWF):</label>
                <input type="number" name="amount" value="<?php echo $amount ?? ''; ?>" required>
                <label>Enter PIN:</label>
                <input type="password" name="pin" required>
            <?php endif; ?>

            <input type="submit" value="Submit">
        </form>
    <?php endif; ?>

    <div>
        <?php echo $transactionResult; ?>
    </div>
</body>
</html>
