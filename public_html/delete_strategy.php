<?php
include 'common/auth.php';
include 'common/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['strategy_id'])) {
    $strategy_id = $_POST['strategy_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = oci_parse($conn, "SELECT 1 FROM CustomStrategy WHERE strategy_id = :strat_id AND user_id = :username");
    oci_bind_by_name($stmt, ':strat_id', $strategy_id);
    oci_bind_by_name($stmt, ':username', $user_id);
    oci_execute($stmt);

    if (oci_fetch($stmt)) {
        $del = oci_parse($conn, "DELETE FROM Strategy WHERE strategy_id = :strat_id");
        oci_bind_by_name($del, ':strat_id', $strategy_id);
        oci_execute($del);
        oci_commit($conn);
    }
}

header("Location: account.php?section=strategies");
exit();
