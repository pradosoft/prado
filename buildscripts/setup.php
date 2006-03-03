<?php
/**
 * Setup script for build system. You could run this script to install or upgrade Phing
 * with dependencies. Please make sure to change the PEAR_CMD constant to how you want
 * to execute the PEAR installer.
 * 
 * @author Knut Urdalen
 */

// Where to find the PEAR installer
define('PEAR_CMD', 'sudo pear');

// Storing your preferred_state
$preferred_state = exec(PEAR_CMD.' config-get preferred_state');

// Setting preferred state temporary to development to automatically get all dependencies
system(PEAR_CMD.' config-set preferred_state devel');

// Ensure that the PEAR channel protocol is updated
system(PEAR_CMD.' channel-update pear.php.net');

// Ensure that the Phing PEAR channel is added
system(PEAR_CMD.' channel-discover pear.phing.info');

// and channel protocol is updated
system(PEAR_CMD.' channel-update pear.phing.info');

// Checking if Phing is already installed
$result = exec(PEAR_CMD.' info phing/phing');
if(strstr($result, 'No information found for')) { // Install
  system(PEAR_CMD.' install --alldeps phing/phing');
} else { // Try to upgrade
  system(PEAR_CMD.' upgrade --alldeps phing/phing');
}

// Setting your preferred state back to what it was
system(PEAR_CMD.' config-set preferred_state '.$preferred_state);

?>