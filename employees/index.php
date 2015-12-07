<?php
	//get json file from folder
    $file = dirname(__FILE__) . "/employees.json";
	$json = file_get_contents($file);

    // open mysql connection
    $host = "localhost";
    $username = "lup";
    $password = "lup";
    $dbname = "lup";
    $con = mysqli_connect($host, $username, $password, $dbname) or die('Error in Connecting: ' . mysqli_error($con));

    // use prepare statement for insert query
    $st = mysqli_prepare($con, 'INSERT INTO employees(id, `Company Code`, Company, `ID Number`, `Employee Name`, `Last Name`, `First Name`, `User Name`, `E-Mail Address`, `Local Grade`, `Cost Center`, `Direct Manager ID`, `Direct Manager`, `Department Head ID`, `Department Head`, `Department`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    // bind variables to insert query params
    mysqli_stmt_bind_param($st, 'issssssssssisiss', $id, $company_code, $company, $id_number, $employee_name, $last_name, $first_name, $logon_id, $email, $local_grade, $cost_center, $manager_id, $manager, $chief_id, $chief, $department);
   
    //convert json object to php associative array
    $data = json_decode($json, true);
    //var_dump($data);
    // loop through the array
    foreach ($data as $row) {
        // get the employee details
        $id = $row['PERNR'];
        $company_code = $row['BUKRS'];
        $company = $row['BUTXT'];
        $id_number = $row['ZZPERID'];
        $employee_name = $row['VORNA'] . ' ' . $row['NACHN'];
        $last_name = $row['NACHN'];
        $first_name = $row['VORNA'];
        $logon_id = $row['ZZUSERID'];
        $email = $row['ZZMAIL'];
        $local_grade = $row['ZZLCGDE'];
        $cost_center = $row['KOSTL'];
        $manager_id = $row['ZZHRMAN_PERNR'];
        $manager = $row['ZZHRMAN_VORNA'] . ' ' . $row['ZZHRMAN_NACHN'];
        $department = $row['ZZDEP_OM_TXT'];
        $chief_id = $row["ZZCHIEF_DEP_PERN"];
        $chief = $row["ZZCHIEF_DEP_VORN"] . ' ' . $row["ZZCHIEF_DEP_NACH"];

        // execute insert query
        mysqli_stmt_execute($st);
    }
    
    //close connection
    mysqli_close($con);

?>
