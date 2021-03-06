<?php

use src\Models\ApplicationContainer;
use src\Models\OcConfig\OcConfig;
use src\Utils\Database\XDb;
use src\Utils\Text\Validator;

global $absolute_server_URI;

require_once(__DIR__ . '/lib/common.inc.php');

$user = ApplicationContainer::GetAuthorizedUser();
if (!$user) {
    $target = urlencode(tpl_get_current_page());
    tpl_redirect('login.php?target=' . $target);
}


//set here the template to process
$tplname = 'newemail';

//load language specific variables
require_once(__DIR__ . '/src/Views/' . $tplname . '.inc.php');

tpl_set_var('new_email', '');
tpl_set_var('message', '');
tpl_set_var('email_message', '');
tpl_set_var('code_message', '');
tpl_set_var('change_email', $change_email);
tpl_set_var('reset', tr('reset'));
tpl_set_var('getcode', $get_code);

if (isset($_POST['submit_getcode']) || isset($_POST['submit_changeemail'])) {
    $new_email = $_POST['newemail'];

    tpl_set_var('new_email', htmlspecialchars($new_email, ENT_COMPAT, 'UTF-8'));

    //validate the email
    $email_exists = false;
    $new_email_not_ok = false;

    if (!Validator::isValidEmail($new_email)) {
        $new_email_not_ok = true;
        tpl_set_var('email_message', $error_email_not_ok);
    } else {
        //prüfen, ob email schon in der Datenbank vorhanden
        $rs = XDb::xSql(
            "SELECT `username` FROM `user` WHERE `email`= ? ", $new_email);
        if (false !== XDb::xFetchArray($rs)) {
            $email_exists = true;
            tpl_set_var('email_message', $error_email_exists);
        }
    }

    if ((!$email_exists) && (!$new_email_not_ok)) {
        if (isset($_POST['submit_getcode'])) {
            //send the secure code via email and store the new email in the database
            $secure_code = uniqid('');

            //code in DB eintragen
            XDb::xSql(
                "UPDATE `user` SET `new_email_date`=?, `new_email_code`=?, `new_email`=?
                        WHERE `user_id`=?", time(), $secure_code, $new_email, $user->getUserId());

            $email_content = file_get_contents(__DIR__ . '/resources/email/newemail.email');
            $email_content = mb_ereg_replace('{server}', $absolute_server_URI, $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_01}', tr('newEmailAddr_01'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_02}', tr('newEmailAddr_02'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_03}', tr('newEmailAddr_03'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_04}', tr('newEmailAddr_04'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_05}', tr('newEmailAddr_05'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_06}', tr('newEmailAddr_06'), $email_content);
            $email_content = mb_ereg_replace('{newEmailAddr_07}', tr('newEmailAddr_07'), $email_content);
            $email_content = mb_ereg_replace('{user}', $user->getUserName(), $email_content);
            $email_content = mb_ereg_replace('{date}', strftime(
                $GLOBALS['config']['datetimeformat']), $email_content);
            $email_content = mb_ereg_replace('{code}', $secure_code, $email_content);
            $email_content = mb_ereg_replace('{octeamEmailsSignature}', OcConfig::getOcteamEmailsSignature(), $email_content);

            $emailheaders = "Content-Type: text/plain; charset=utf-8\r\n";
            $emailheaders .= "Content-Transfer-Encoding: 8bit\r\n";
            $emailheaders .= 'From: "' . OcConfig::getEmailAddrNoReply() . '" <' . OcConfig::getEmailAddrNoReply() . '>';


            mb_send_mail($new_email, $email_subject, $email_content, $emailheaders);

            tpl_set_var('message', $email_send);
        } else if (isset($_POST['submit_changeemail'])) {
            $secure_code = $_POST['code'];

            $rs = XDb::xSql(
                "SELECT `new_email_code`, `new_email`, `new_email_date` FROM `user` WHERE `user_id`=? ",
                $user->getUserName());

            $record = XDb::xFetchArray($rs);

            if ($record !== false) {
                $new_email = $record['new_email'];
                $new_email_code = $record['new_email_code'];
                $new_email_date = $record['new_email_date'];
            } else {
                $new_email = '';
            }

            if ($new_email == '') {
                //no new email was entered
                tpl_set_var('message', $error_no_new_email);
            } else if ($new_email_code != $secure_code) {
                //wrong code
                tpl_set_var('code_message', $error_wrong_code);
            } else if (time() - $new_email_date > 259200) {
                //code timed out
                tpl_set_var('code_message', $error_code_timed_out);
            } else {
                //check if email exists
                $rs = XDb::xSql(
                    "SELECT `username` FROM `user` WHERE `email`= ? ", $new_email);
                if (false !== XDb::xFetchArray($rs)) {
                    tpl_set_var('message', $error_email_exists);
                } else {
                    //neue EMail eintragen
                    XDb::xSql(
                        "UPDATE `user` SET `new_email_date`=NULL, `new_email_code`=NULL,
                                        `new_email`=NULL, `email`= ? , `last_modified`=NOW()
                                WHERE `user_id`= ? ",
                        $new_email, $user->getUserId());

                    //try to change the email
                    tpl_set_var('message', $email_changed);
                }
            }
        }
    }
}

//make the template and send it out
tpl_BuildTemplate();
