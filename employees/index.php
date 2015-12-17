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
    $con = mysqli_connect($host, $username, $password, $dbname) 
        or die('Error in Connecting: ' . mysqli_error($con));

    // use prepare statement for insert query
    $st = mysqli_prepare($con, 'INSERT INTO _soi_data(PERNR, STAT2, ZZHIRE_DATE, ZZTERM_DATE, BUKRS, PERSG, ZZPERSG_TXT, BUTXT, KOSTL, WERKS, ZZWERKS_TXT, ZZDEP_OM, ZZDEP_OM_TXT, ZZHRMAN_VORNA, ZZHRMAN_NACHN, ZZHRMAN_PERNR, ZZHRMAN_USERID, ZZHRMAN_MAIL, ZZHRREP_VORNA, ZZHRREP_NACHN, ZZHRREP_PERNR, ZZCHIEF_DEP_VORN, ZZCHIEF_DEP_NACH, ZZCHIEF_DEP_PERN, NACHN, VORNA, GESCH, ZZGESCH_TXT, ZZMAIL, ZZUSERID, FATXT, ZZLEGACY_ID, ZZLCGDE, ZZUPD_DATE, ZZTYPE_UPD, ZZPERID) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        or die("Error in Preparing: " . mysqli_error($con));
    $update = mysqli_prepare($con, 'INSERT INTO _soi_data_updated(PERNR, STAT2, ZZHIRE_DATE, ZZTERM_DATE, BUKRS, PERSG, ZZPERSG_TXT, BUTXT, KOSTL, WERKS, ZZWERKS_TXT, ZZDEP_OM, ZZDEP_OM_TXT, ZZHRMAN_VORNA, ZZHRMAN_NACHN, ZZHRMAN_PERNR, ZZHRMAN_USERID, ZZHRMAN_MAIL, ZZHRREP_VORNA, ZZHRREP_NACHN, ZZHRREP_PERNR, ZZCHIEF_DEP_VORN, ZZCHIEF_DEP_NACH, ZZCHIEF_DEP_PERN, NACHN, VORNA, GESCH, ZZGESCH_TXT, ZZMAIL, ZZUSERID, FATXT, ZZLEGACY_ID, ZZLCGDE, ZZUPD_DATE, ZZTYPE_UPD, ZZPERID) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        or die("Error in Preparing: " . mysqli_error($con));
  /*  $wp_users = mysqli_prepare($con, 'INSERT IGNORE INTO wp_users(ID, user_login, user_email, user_nicename, display_name, employee_id, company_code, company, id_number, last_name, first_name, logon_id, local_grade, cost_center, direct_manager_id, direct_manager, department_head_id, department_head, department, update_date) 
   *                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
   *     or die("Error in Preparing: " . mysqli_error($con));
   */

    // bind variables to insert query params
    mysqli_stmt_bind_param($st, 'issssssssssssssissssissississsssssss', $id, $stat2, $hire_date, $term_date, $company_code, $persg, $persg_txt, $company, $cost_center, $werks, $site, $dep_om, $department, $manager_firnm, $manager_lasnm, $manager_id, $manager_userid, $manager_email, $hr_firnm, $hr_lasnm, $hr_id, $chief_firnm, $chief_lasnm, $chief_id, $last_name, $first_name, $gesch, $gender, $email, $logon_id, $fatxt, $legacy_id, $local_grade, $update_date, $type_upd, $id_number)
        or die("Error in banding: " . mysqli_error($con));
    mysqli_stmt_bind_param($update, 'issssssssssssssissssissississsssssss', $id, $stat2, $hire_date, $term_date, $company_code, $persg, $persg_txt, $company, $cost_center, $werks, $site, $dep_om, $department, $manager_firnm, $manager_lasnm, $manager_id, $manager_userid, $manager_email, $hr_firnm, $hr_lasnm, $hr_id, $chief_firnm, $chief_lasnm, $chief_id, $last_name, $first_name, $gesch, $gender, $email, $logon_id, $fatxt, $legacy_id, $local_grade, $update_date, $type_upd, $id_number)
        or die("Error in banding: " . mysqli_error($con));   
    // mysqli_stmt_bind_param($wp_users, 'isssssssssssssisisss', $id, $email, $email, $employee_name, $employee_name, $id, $company_code, $company, $id_number, $last_name, $first_name, $logon_id, $local_grade, $cost_center, $manager_id, $manager, $chief_id, $chief, $department, $update_date)
    //     or die("Error in banding: " . mysqli_error($con));
    
    //convert json object to php associative array
    $data = json_decode($json, true);
    $data_old = json_decode($json_old, true);
    $diff = array_diff(array_recursive($data, $data_rt), array_recursive($data_old, $data_old_rt));

    // loop through the array
    foreach ($data as $row) {
        // get the employee details
        $id = $row['PERNR'];
        $stat2 = $row['STAT2'];
        $hire_date = $row['ZZHIRE_DATE'];
        $term_date = $row['ZZTERM_DATE'];
        $company_code = $row['BUKRS'];
        $persg = $row['PERSG'];
        $persg_txt = $row['ZZPERSG_TXT'];
        $company = $row['BUTXT'];
        $cost_center = $row['KOSTL'];
        $werks = $row['WERKS'];
        $site = $row['ZZWERKS_TXT'];
        $dep_om = $row['ZZDEP_OM'];
        $department = $row['ZZDEP_OM_TXT'];
        $manager_firnm = $row['ZZHRMAN_VORNA'];
        $manager_lasnm = $row['ZZHRMAN_NACHN'];
        $manager_id = $row['ZZHRMAN_PERNR'];
        $manager_userid = $row['ZZHRMAN_USERID'];
        $manager_email = $row['ZZHRMAN_MAIL'];
        $hr_firnm = $row['ZZHRREP_VORNA'];
        $hr_lasnm = $row['ZZHRREP_NACHN'];
        $hr_id = $row['ZZHRREP_PERNR'];
        $chief_firnm = $row['ZZCHIEF_DEP_VORN'];
        $chief_lasnm = $row['ZZCHIEF_DEP_NACH'];
        $chief_id = $row['ZZCHIEF_DEP_PERN'];
        $last_name = ucwords(strtolower($row['NACHN']));        
        $first_name = ucwords(strtolower($row['VORNA']));
        $gesch = $row['GESCH'];
        $gender = $row['ZZGESCH_TXT'];
        $email = strtolower($row['ZZMAIL']);
        $logon_id = $row['ZZUSERID'];
        $fatxt = $row['FATXT'];
        $legacy_id = $row['ZZLEGACY_ID'];
        $local_grade = $row['ZZLCGDE'];
        $update_date = $row["ZZUPD_DATE"];
        $type_upd = $row['ZZTYPE_UPD'];
        $id_number = $row['ZZPERID'];
        $employee_name = ucwords(strtolower($row['VORNA'] . ' ' . $row['NACHN']));
        $manager = ucwords(strtolower($row['ZZHRMAN_VORNA'] . ' ' . $row['ZZHRMAN_NACHN']));
        $chief = ucwords(strtolower($row["ZZCHIEF_DEP_VORN"] . ' ' . $row["ZZCHIEF_DEP_NACH"]));
        
        
        foreach ($diff as $row2) {

            if ($row2 == $update_date) {
                mysqli_stmt_execute($update);
            }
        }
        // mysqli_stmt_execute($wp_users)
        //     or die("Error in executing: " . mysqli_error($con));
        mysqli_stmt_execute($st)
            or die("Error in executing: " . mysqli_error($con));  
    }

    
    //close connection
    mysqli_close($con);

    echo "Succeed!";
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