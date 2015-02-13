<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
if (isset($_post['pb_ajax_hack']) && $_post['pb_ajax_hack'] == 'true') {
    $email = '';
    $chdata = "Submit=Subscribe&pf_CharSet=utf-8";

    foreach ($_GET as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $chdata .= "&" . $key . "=" . $v;
            }
        } else {
            $chdata .= "&" . $key . "=" . $value;
            if ($key == "pf_Email") {
                $email = $value;
            }
            if ($key == "redirect") {
                $redirect = $value;
            }
        }
    }
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $chdata .= "&" . $key . "=" . $v;
            }
        } else {
            $chdata .= "&" . $key . "=" . $value;
            if ($key == "pf_Email") {
                $email = $value;
            }
            if ($key == "redirect") {
                $redirect = $value;
            }
        }
    }

    // send post data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://www.anpdm.com/public/process-subscription-form.aspx?');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $chdata);

    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (isset($redirect)) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $redirect");
        exit;
    } else {
        echo $status;
        return $status;
    }
    die();
}

if (version_compare(phpversion(), '5.2.0', '<') === true) {
    echo '<div style="font:12px/1.35em arial, helvetica, sans-serif;"><div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;"><h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">Whoops, it looks like you have an invalid PHP version.</h3></div><p>Magento supports PHP 5.2.0 or newer. <a href="http://www.magentocommerce.com/install" target="">Find out</a> how to install</a> Magento using PHP-CGI as a work-around.</p></div>';
    exit;
}

/**
 * Error reporting
 */
error_reporting(E_ALL | E_STRICT);

/**
 * Compilation includes configuration file
 */
$compilerConfig = 'includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$mageFilename = '/home/follexln/public_html/app/Mage.php';
$maintenanceFile = 'maintenance.flag';

if (!file_exists($mageFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $mageFilename . " was not found";
    }
    exit;
}

if (file_exists($maintenanceFile)) {
    include_once dirname(__FILE__) . '/errors/503.php';
    exit;
}

require_once $mageFilename;

/*require 'smtp/sasl.php';
require 'smtp/smtp.php';*/

#Varien_Profiler::enable();

if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
    Mage::setIsDeveloperMode(true);
}

#ini_set('display_errors', 1);

umask(0);

/* Store or website code */
$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : 
'follestad_2';

/* Run store or run website */
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'website';


/*
// 09/09/2013: Added temporary basic-auth for second storefront
if (isset($mageRunCode) AND $mageRunCode == 'follestad_2') {
    if ($_SERVER['PHP_AUTH_USER'] !== 'pingbull' OR $_SERVER['PHP_AUTH_PW'] !== 'love') {
        unset($_SERVER['PHP_AUTH_USER']);
        unset($_SERVER['PHP_AUTH_PW']);
        header('WWW-Authenticate: Basic realm="Please, enter login/password for entering this website"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'You will have to login here to access development environment';

        exit;
    } else {

        Mage::run($mageRunCode, $mageRunType);

    }
} else {
    Mage::run($mageRunCode, $mageRunType);
}
*/

Mage::run($mageRunCode, $mageRunType);