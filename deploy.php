#!/usr/bin/php
<?php

function execute_command($command)
{
    echo 'EXECUTING... ' . "\n";
    $output_data = array();
    $return_value = exec($command, $output_data);

    print_r($output_data);

    return $return_value;
}

function execute_commands($commands_as_array)
{
    foreach ($commands_as_array as $command) {
        echo 'SHELL COMMAND: "' . $command . "\"\n";
        if (execute_command($command) == false) {
            return false;
        }

    }

    return true;
}

// get the deploy scripts location
if (isset($argv[1]) == true) {
    $deploy_scripts_location = $argv[1];
    if (substr($deploy_scripts_location, -1) != '/') {
        exit('ERROR! Trailing slash (/) required for path' . "\n");
    }
} else {
    exit('ERROR! Please provide the deploy scripts location. ie. php deploy.php /var/www/deploy/ 6' . "\n");
}

// get the 'deploy to' version
if (isset($argv[2]) == true) {
    $version_to_deploy_to = $argv[2];
    if (is_numeric($version_to_deploy_to) == false) {
        exit('ERROR! Version # to deploy to must be numeric' . "\n");
    }
} else {
    exit('ERROR! Please provide a version # to deploy to. ie. php deploy.php /var/www/deploy/ 6' . "\n");
}

// get the current version
$current_version = file_get_contents($deploy_scripts_location . '.current_version');
if ($current_version == false) {
    file_put_contents($deploy_scripts_location . '.current_version', '0');
    $current_version = 0;
} else {
    $current_version = trim($current_version);
    $current_version = (int)$current_version;
}

$last_version_deployed_to_via_deploy = '';

if ($current_version < $version_to_deploy_to) {
    // deploy up!
    echo 'Current version: "' . $current_version . '". Update to version: "' . $version_to_deploy_to . '"? (y|n) ...' . "\n";
    if (trim(fgets(STDIN)) != 'y') {
        exit('Successfully canceled deployment.' . "\n");
    }

    for ($i = $current_version + 1; $i <= $version_to_deploy_to; $i++) {
        $deploy_script = file_exists($deploy_scripts_location . $i . '.json');
        if ($deploy_script == false) {
            echo 'WARNING! No deploy script found for version ' . $i . "\n";
            continue; // no file found
        }

        $commands = file_get_contents($deploy_scripts_location . $i . '.json');
        if ($commands == false) {
            exit('ERROR! Failed to get contents of "' . $deploy_script . '"' . "\n");
        }

        $commands = json_decode($commands);

        if (isset($commands->up) == false) {
            exit('ERROR! No commands found for "up". Cannot update to version: ' . $i . "\n");
        }

        if (isset($commands->down) == false) {
            exit('ERROR! No commands found for "down". Cannot update to version: ' . $i . "\n");
        }

        echo 'Attempting to update to version: ' . $i . "\n";
        if (execute_commands($commands->up) == false) {
            // revert!
            echo 'Failed to update! Attempting to revert...' . "\n";
            for ($j = $i; $j >= $current_version; $j--) {
                $commands = json_decode(file_get_contents($deploy_scripts_location . $j . '.json'));

                if (execute_commands($commands->down) == false) {
                    exit('ERROR! Failed to revert to version: ' . $j . "\n" . 'ERROR! Current version: ' . $last_version_deployed_to_via_deploy . "\n");
                } else {
                    file_put_contents($deploy_scripts_location . '.current_version', $j);
                    $last_version_deployed_to_via_deploy = $j;
                    echo 'Reverted to version: ' . $j . "\n";
                }
            }
            exit('Successfully reverted to original version' . "\n"); // quit
        } else {
            file_put_contents($deploy_scripts_location . '.current_version', $i);
            $last_version_deployed_to_via_deploy = $i;
            echo 'Updated to version: ' . $i . "\n\n";
        }
    }
} else if ($current_version > $version_to_deploy_to) {
    // deploy down!
    echo 'Current version: "' . $current_version . '". Revert to version: "' . $version_to_deploy_to . '"? (y|n)' . "\n";
    if (trim(fgets(STDIN)) != 'y') {
        exit('Successfully canceled deployment.' . "\n");
    }

    for ($i = $current_version; $i >= $version_to_deploy_to; $i--) {
        $deploy_script = file_exists($deploy_scripts_location . $i . '.json');
        if ($deploy_script == false) {
            echo 'WARNING! No deploy script found for version ' . $i . "\n";
            continue; // no file found
        }

        $commands = file_get_contents($deploy_scripts_location . $i . '.json');
        if ($commands == false) {
            exit('ERROR! Failed to get contents of "' . $deploy_script . '"' . "\n");
        }

        $commands = json_decode($commands);

        if (isset($commands->up) == false) {
            exit('ERROR! No commands found for "up". Cannot update to version: ' . $i . "\n");
        }

        if (isset($commands->down) == false) {
            exit('ERROR! No commands found for "down". Cannot update to version: ' . $i . "\n");
        }

        echo 'Attempting to revert to version: ' . $i . "\n";
        if (execute_commands($commands->down) == false) {
            // revert!
            echo 'Failed to revert! Attempting to update...' . "\n";
            for ($j = $i; $j <= $current_version; $j++) {
                $commands = json_decode(file_get_contents($deploy_scripts_location . $j . '.json'));

                if (execute_commands($commands->up) == false) {
                    exit('ERROR! Failed to update to version: ' . $j . "\n" . 'ERROR! Current version: ' . $last_version_deployed_to_via_deploy . "\n");
                } else {
                    file_put_contents($deploy_scripts_location . '.current_version', $j);
                    $last_version_deployed_to_via_deploy = $j;
                    echo 'Updated to version: ' . $j . "\n";
                }
            }
            exit('Successfully updated to original version' . "\n");
        } else {
            file_put_contents($deploy_scripts_location . '.current_version', $i);
            $last_version_deployed_to_via_deploy = $i;
            echo 'Reverted to version: ' . $i . "\n\n";
        }
    }
} else {
    // do nothing! same version
}

exit('Successfuly migrated from version "' . $current_version . '" to "' . $last_version_deployed_to_via_deploy . '"' . "\n");
