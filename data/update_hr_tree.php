<?php
/**
 * This script is used to update departmental hierarchy data from an XML file.
 * 
 * Jim Liebgott maintains a process which scp's this file to directory.unl.edu.
 * A cron job runs nightly to update the data.
 */
require_once dirname(__FILE__).'/../www/config.inc.php';
error_reporting(E_ALL | E_STRICT);
set_time_limit(0);
$sap_dept = new UNL_Peoplefinder_Department(array('d'=>'University of Nebraska - Lincoln'));

if (!($root = UNL_Officefinder_Department::getByID(1))) {
    throw new Exception('Could not find the root element!');
}

// Start updating at the 'root' of UNL's info within the XML tree
updateOfficialDepartment($sap_dept);

/**
 * Method for recursively monitoring a peoplefinder (SAP/XML department) and updating the related
 * records within the directory (MySQL Officefinder data).
 *
 * @param $sap_dept The XML department object
 * @param $parent   The MySQL ORM department
 */
function updateOfficialDepartment(UNL_Peoplefinder_Department $sap_dept, UNL_Officefinder_Department &$parent = null)
{

    if (!($dept = UNL_Officefinder_Department::getByorg_unit($sap_dept->org_unit))) {
        // Uhoh, new department!
        $dept = new UNL_Officefinder_Department();

        // Now update all fields with the official data from SAP
        updateFields($dept, $sap_dept);
        
        echo 'New department found:'.$dept->name.' ('.$dept->org_unit.')'.PHP_EOL;
    }

    if ($parent) {
        if ($dept->isChildOf($parent)) {
            // All OK!
        } else {
            if (isset($dept->parent_id)) {
                // This department has moved
                echo 'Department move:'.$dept->name.' has moved from '.UNL_Officefinder_Department::getByID($dept->parent_id)->name.' to '.$parent->name.PHP_EOL;
            }
            $parent->addChild($dept, true);
        }
    }

    if ($sap_dept->hasChildren()) {
        foreach ($sap_dept->getChildren() as $sap_sub_dept) {
            updateOfficialDepartment($sap_sub_dept, $dept);
        }
    }
}

/**
 * This method is used to update data within the UNL directory. It allows updates
 * only on certain fields.
 *
 * @param $old Object with the old data
 * @param $new Object with the new data
 */
function updateFields(UNL_Officefinder_Department $old, UNL_Peoplefinder_Department $new)
{
    foreach ($old as $key=>$val) {
        if (isset($new->$key)
            && $key != 'options') {
            $old->$key = $new->$key;
        }
        // Save it
        $old->save();
        if (!empty($new->org_abbr)) {
            $old->addAlias($new->org_abbr);
        }
    }
}
