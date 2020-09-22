<?php

require_once(__DIR__ . '/../../system/common.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-type: text/plain; charset=utf-8");

$system_user_id = 1;

// Get Member role id
$sql = "SELECT rol_id FROM ".TBL_ROLES." WHERE rol_name = ?";
$queryParams = array("Member");
$rolesStatement = $gDb->queryPrepared($sql, $queryParams);

$member_rol_id = 0;
while ($row = $rolesStatement->fetch())
{
    $member_rol_id = $row['rol_id'];
}
echo "Member rol_id = $member_rol_id\nº";

// List paid users
$sql = "SELECT pay_user from mws__payments inner join mws__contribution_fees on mws__contribution_fees.fee_id = mws__payments.pay_contribution_id where mws__contribution_fees.fee_from<CURRENT_TIMESTAMP and mws__contribution_fees.fee_to>CURRENT_TIMESTAMP and mws__payments.pay_status = 1";

$queryParams = array();
$stmt = $gDb->queryPrepared($sql, $queryParams);

$paid_user_ids = [];
while ($row = $stmt->fetch())
{
    $pay_id = $row['pay_user'];
    $paid_user_ids[] = $pay_id;
    $sql = "SELECT (mem_end > NOW()) as ok FROM ".TBL_MEMBERS." WHERE mem_rol_id = ? AND mem_usr_id = ?";
    $queryParams = array($member_rol_id, $pay_id);
    $stmt_mem = $gDb->queryPrepared($sql, $queryParams);

    if (!$row = $stmt_mem->fetch()) {
        echo "Not a member $pay_id... Adding\n";
        
        $queryParams = array($member_rol_id, $pay_id, $system_user_id);
        $sql = "INSERT INTO ".TBL_MEMBERS." (`mem_id`, `mem_rol_id`, `mem_usr_id`, `mem_begin`, `mem_end`, `mem_leader`, `mem_usr_id_create`, `mem_timestamp_create`, `mem_usr_id_change`, `mem_timestamp_change`, `mem_approved`, `mem_comment`, `mem_count_guests`) VALUES (NULL, ?, ?, NOW(), '9999-12-31', '0', ?, NOW(), NULL, NULL, NULL, NULL, '0')";

        $gDb->queryPrepared($sql, $queryParams);
    } else {
        if (!intval($row['ok'])) {
            echo "Not a member $pay_id... Updating\n";
            $sql = "UPDATE ".TBL_MEMBERS." SET mem_begin = NOW(), mem_end = '9999-12-31' WHERE mem_rol_id = ? AND mem_usr_id = ?";
            $queryParams = array($member_rol_id, $pay_id);
            $gDb->queryPrepared($sql, $queryParams);
        }
    }
}

echo "Paid members";
print_r($paid_user_ids);

// Delete non-paying member
$list = implode(",", $paid_user_ids);

$queryParams = array($member_rol_id, $pay_id, $system_user_id);
$sql = "UPDATE ".TBL_MEMBERS." SET mem_end = NOW() WHERE mem_end > NOW() AND mem_rol_id = ? and mem_usr_id NOT IN ";
$sql .= "(";
$sql .= $list;
$sql .= ")";

$queryParams = array($member_rol_id);

$gDb->queryPrepared($sql, $queryParams);

