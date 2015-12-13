<?php
	//get json file from folder
    $file = dirname(__FILE__) . "/employees.json";
	  $json = file_get_contents($file);
    $file_old = dirname(__FILE__) . "/employees_old.json";
    $json_old = file_get_contents($file_old);

    // open mysql connection
    $host = "localhost";
    $username = "";
    $password = "";
    $dbname = "";
    $con = mysqli_connect($host, $username, $password, $dbname) or die('Error in Connecting: ' . mysqli_error($con));

    // use prepare statement for insert query
    $st = mysqli_prepare($con, 'INSERT INTO employees(id, `Company Code`, Company, `ID Number`, `Employee Name`, `Last Name`, `First Name`, `User Name`, `E-Mail Address`, `Local Grade`, `Cost Center`, `Direct Manager ID`, `Direct Manager`, `Department Head ID`, `Department Head`, `Department`, `Update Date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $update = mysqli_prepare($con, 'INSERT INTO employees_updated(id, `Company Code`, Company, `ID Number`, `Employee Name`, `Last Name`, `First Name`, `User Name`, `E-Mail Address`, `Local Grade`, `Cost Center`, `Direct Manager ID`, `Direct Manager`, `Department Head ID`, `Department Head`, `Department`, `Update Date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    // bind variables to insert query params
    mysqli_stmt_bind_param($st, 'issssssssssisisss', $id, $company_code, $company, $id_number, $employee_name, $last_name, $first_name, $logon_id, $email, $local_grade, $cost_center, $manager_id, $manager, $chief_id, $chief, $department, $update_date);
    mysqli_stmt_bind_param($update, 'issssssssssisisss', $id, $company_code, $company, $id_number, $employee_name, $last_name, $first_name, $logon_id, $email, $local_grade, $cost_center, $manager_id, $manager, $chief_id, $chief, $department, $update_date);

    //convert json object to php associative array
    $data = json_decode($json, true);
    $data_old = json_decode($json_old, true);
    $diff = array_diff(array_recursive($data, $data_rt), array_recursive($data_old, $data_old_rt));
    print_r(array_recursive($data, $data_rt));
    print_r($diff);
    // exit;
    //  var_export($diff);
    //var_export($data);
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
        $update_date = $row["ZZUPD_DATE"];

        foreach ($diff as $row2) {
          // $update_date2 = $row2["ZZUPD_DATE"];
          //  if ($update_date2 == $update_date) {
          //      mysqli_stmt_execute($update);
            if ($row2 == $update_date) {
              mysqli_stmt_execute($update);
            }
        }

        mysqli_stmt_execute($st);  
    }

    
    //close connection
    mysqli_close($con);

/*
function array_diff_assoc_recursive($array1,$array2){  
    $diffarray=array();  
    foreach ($array1 as $key=>$value){  
      //判断数组每个元素是否是数组  
     if(is_array($value)){  
      //判断第二个数组是否存在key  
       if(!isset($array2[$key])){  
           $diffarray[$key]=$value;  
       //判断第二个数组key是否是一个数组  
       }elseif(!is_array($array2[$key])){  
           $diffarray[$key]=$value;  
       }else{  
           $diff=array_diff_assoc_recursive($value, $array2[$key]);  
           if($diff!=false){  
             $diffarray[$key]=$diff;  
           }  
       }  
     }elseif(!array_key_exists($key, $array2) || $value!==$array2[$key]){  
          $diffarray[$key]=$value;  
     }  
    }  
    return $diffarray;    
}  
*/

function array_recursive($array, &$rt) {
      if (is_array($array)) {
          foreach ($array as $v) {
              if (is_array($v)) {
                  array_recursive($v, $rt);
              } else {
                  $rt[] = $v;
              }
          }
      }
      return $rt;
  }

?>