<?php
class UNL_Officefinder_DepartmentList_OrgUnit extends UNL_Officefinder_DepartmentList
{
    public $options = array('q'=>'');

    function __construct($options = array())
    {
        $this->options = $options + $this->options;
        $records = array();
        $mysqli = UNL_Officefinder::getDB();
        $sql = 'SELECT id, name FROM departments ';
        $sql .= 'WHERE org_unit = "'.$mysqli->escape_string($this->options['q']).'"'
             . ' ORDER BY name';
        if ($result = $mysqli->query($sql)) {
            while($row = $result->fetch_array(MYSQLI_NUM)) {
                $records[] = $row[0];
            }
        }
        parent::__construct($records);
    }

}