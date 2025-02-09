<?php
$dbuser="root";		
$dbpassword=""; 	
$dbname="money"; 	
$dbhost="localhost";

$connection = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

// Check connection
if (!$connection) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

$msgBoxExpense='';
//Include Functions
include('includes/db.php');
include('includes/Functions.php');

//Include Notifications
include ('includes/notification.php');


//save income form
if(isset($_POST['income'])){
    $iuser          = $_SESSION['UserId'];
    $iname          = $mysqli->real_escape_string($_POST["iname"]);
    $icategory      = $mysqli->real_escape_string($_POST["icategory"]);
    $iaccount       = $mysqli->real_escape_string($_POST["iaccount"]);
    $department_id  = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : 0; // Fixed to use the correct field
    $idescription   = $mysqli->real_escape_string($_POST["idescription"]);
    $idate          = $mysqli->real_escape_string($_POST["idate"]);
    $iamount        = $mysqli->real_escape_string(clean($_POST["iamount"]));

    if ($iuser == '' || $iamount == '' || $department_id == 0) {
        $msgBox = alertBox($MessageEmpty);
    } else {
        if ($iamount < 0) {
            $msgBox = alertBox($NegativeAmount);
        } else {
            // Add new income
            $sql = "INSERT INTO assets (UserId, Title, Date, CategoryId, AccountId, department_id, Amount, Description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if ($statement = $mysqli->prepare($sql)) {
                // Bind parameters for markers
                $statement->bind_param('issiiids', $iuser, $iname, $idate, $icategory, $iaccount, $department_id, $iamount, $idescription);
                if ($statement->execute()) {
                    $msgBox = alertBox($SaveMsgIncome);
                } else {
                    $msgBox = alertBox("Error: " . $mysqli->error);
                }
            } else {
                $msgBox = alertBox("Error in query preparation: " . $mysqli->error);
            }
        }
    }
}

// Save Expense Form
if (isset($_POST['expense'])) {
    $euser         = $_SESSION['UserId']; // Assuming this is the currently logged-in user
    $ename         = $mysqli->real_escape_string($_POST["ename"]);
    $ecategory     = $mysqli->real_escape_string($_POST["ecategory"]);
    $eaccount      = $mysqli->real_escape_string($_POST["eaccount"]);
    $department_id = "ddd"; // Ensure it's an integer
    $edescription  = $mysqli->real_escape_string($_POST["edescription"]);
    $edate         = $mysqli->real_escape_string($_POST["edate"]);
    $eamount       = $mysqli->real_escape_string(clean($_POST["eamount"]));

    // Debug: var_dump the variables to ensure they are set correctly
    // var_dump($euser, $ename, $ecategory, $eaccount, $department_id, $edescription, $edate, $eamount);

    // Validation check
    if ($ename == '' OR $eamount == '' OR $department_id == 0) {
        $msgBox = alertBox($MessageEmpty);
    } else {
        if ($eamount < 0) {
            $msgBoxExpense = alertBox($NegativeAmount);
        } else {
            $sql = "INSERT INTO bills (UserId, department_id, Title, Dates, CategoryId, AccountId, Amount, Description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($statement = $mysqli->prepare($sql)) {
                if ($statement->bind_param('iissiiis', $euser, $department_id, $ename, $edate, $ecategory, $eaccount, $eamount, $edescription)) {
                    if ($statement->execute()) {
                        $msgBoxExpense = alertBox($SaveMsgExpense);
                    } else {
                        $msgBoxExpense = alertBox("Error executing query: " . $statement->error);
                    }
                } else {
                    $msgBoxExpense = alertBox("Error binding parameters: " . $statement->error);
                }
            } else {
                $msgBoxExpense = alertBox("Error preparing the query: " . $mysqli->error);
            }
        }
    }
}

?>        
        
        <!-- Page Content -->
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
				    <h1 class="page-header"><?php echo $Transaction ;?></h1>
                </div>
            </div>
            <div class="row">
                  <div class="col-lg-6 ">
					   <?php if ($msgBoxExpense) { echo $msgBoxExpense; } ?>
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <i class="fa fa-minus"></i> <?php echo $Expenses ;?>
                        </div>
                            <div class="panel-body">
                                <form action="" method="post" role="form">
                                    <fieldset>
                                    <div class="form-group col-lg-6">
                                        <label for="ename"><?php echo $Name ;?></label>
                                        <input class="form-control" required placeholder="<?php echo $Name ;?>" name="ename" type="text" autofocus>
                                    </div>
                                     <div class="form-group col-lg-5">
										 <label for="eamount" class="control-label"><?php echo $Amount ;?></label> 
											 <div class="input-group">
												 <span class="input-group-addon"><?php echo $ColUser['Currency'];?></span>                                      
												 <input class="form-control" required placeholder="<?php echo $Amount ;?>"  id="iamount" name="eamount" type="text" value="">
											 </div>
                                   </div>
                                   <div class="form-group  col-lg-6">
                                        <label for="ecategory"><?php echo $Category ;?></label>
                                        <select name="ecategory" class="form-control">
                                            <?php while($col = mysqli_fetch_assoc($expense)){ ?>
                                            <option value="<?php echo $col['CategoryId'];?>"><?php echo $col['CategoryName'];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>                                 
                                   
                                   <div class="form-group  col-lg-4">
                                         <label for="eaccount"><?php echo $Account ;?></label>
                                        <select name="eaccount" class="form-control">
                                             <?php while($col = mysqli_fetch_assoc($AccountExpense)){ ?>
                                            <option value="<?php echo $col['AccountId'];?>"><?php echo $col['AccountName'];?></option>
                                            <?php } ?>
                                        </select>
                                   </div>
                                   <div class="form-group col-lg-6">
                                        <label for="user_id">User</label>
                                        <select name="user_id" class="form-control" required>
                                            <option value="">-- Select User --</option>                                             
                                            <?php
                                            // Fetch users from department_user table
                                            $query = "SELECT UserId, FirstName FROM department_user";
                                            $result = mysqli_query($connection, $query);

                                            // Populate the dropdown with User IDs and names
                                            if ($result) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . $row['UserId'] . "'>" . $row['FirstName'] . "</option>";
                                                }
                                            } else {
                                                echo "<option value=''>No users found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                        
                                    <div class="form-group col-lg-4" id="expense">
                                         <label for="edate"><?php echo $Date ;?></label>
                                        <div class="input-group date">
											<input name="edate" class="form-control" type="text"  value="<?php echo date("Y-m-d");?>">
											<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										</div>
                                   </div>

                                   
                                     <div class="form-group col-lg-12 clearbothh">
                                         <label for="edescription"><?php echo $Description ;?></label>
                                        <textarea name="edescription" class="form-control"></textarea>
                                   </div>                             
                                </fieldset>
                               
                            </div>
                            <div class="panel-footer">
                            <button type="submit" name="expense" class="btn btn-warning btn-block"><span class="glyphicon glyphicon-log-in"></span>  <?php echo $SaveExpense ;?></button>
                           </form>
                        </div>
                     </div>
                    </div>
                 
                
                  <div class="col-lg-6 ">
					  <?php if ($msgBox) { echo $msgBox; } ?>
		            <div class="panel panel-primary">
                        <div class="panel-heading">
                           <i class="fa fa-plus"></i> <?php echo $Incomes ;?>
                        </div>
                            <div class="panel-body">
                                <form role="form" method="post" action="">
                                    <fieldset>
                                    <div class="form-group col-lg-6">
								        <label for="iname"><?php echo $Name ;?></label>
                                        <input class="form-control"  required placeholder="<?php echo $Name ;?>" name="iname" type="text" autofocus>
                                    </div>
                                    
                                    <div class="form-group col-lg-5">
										 <label for="iamount" class="control-label"><?php echo $Amount ;?></label> 
											 <div class="input-group">
												 <span class="input-group-addon"><?php echo $ColUser['Currency'];?></span>                                      
												 <input class="form-control" required placeholder="<?php echo $Amount ;?>"  id="iamount" name="iamount" type="text" value="">
											 </div>
                                   </div>
                                   <div class="form-group col-lg-6">
                                        <label for="icategory"><?php echo $Category ;?></label>
                                        <select name="icategory" class="form-control">
										<?php while($col = mysqli_fetch_assoc($income)){ ?>
                                            <option value="<?php echo $col['CategoryId'];?>"><?php echo $col['CategoryName'];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                                                     
                                   <div class="form-group col-lg-4">
                                         <label for="iaccount"><?php echo $Account ;?></label>
                                        <select name="iaccount" class="form-control">
                                            <?php while($col = mysqli_fetch_assoc($AccountIncome)){ ?>
                                            <option value="<?php echo $col['AccountId'];?>"><?php echo $col['AccountName'];?></option>
                                            <?php } ?>
                                        </select>
                                   </div>
                                   <div class="form-group col-lg-6">
                                        <label for="user_id">User</label>
                                        <select name="user_id" class="form-control" required>
                                            <option value="">-- Select User --</option>                                             
                                            <?php
                                            // Fetch users from department_user table
                                            $query = "SELECT UserId, FirstName FROM department_user";
                                            $result = mysqli_query($connection, $query);

                                            // Populate the dropdown with User IDs and names
                                            if ($result) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='" . $row['UserId'] . "'>" . $row['FirstName'] . "</option>";
                                                }
                                            } else {
                                                echo "<option value=''>No users found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                   <div class="form-group col-lg-6" id="income">
                                         <label for="idate"><?php echo $Date ;?></label>
                                        <div class="input-group date">
											<input name="idate" class="form-control" type="text"  value="<?php echo date("Y-m-d");?>">
											<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
										</div>
                                   </div>
                                     <div class="form-group col-lg-12 clearbothh">
                                         <label for="idescription"><?php echo $Description ;?></label>
                                        <textarea name="idescription" class="form-control"></textarea>
                                   </div>                             
                                </fieldset>
                               
                            </div>
                            <div class="panel-footer">
                            <button type="submit" name="income" class="btn btn-success btn-block"><span class="glyphicon glyphicon-log-in"></span>  <?php echo $SaveIncome ;?></button>
							</form>
                        </div>
                         </div>
                    </div>
                 </div>
            </div>
        </div>
 <script>
$(document).on('keyup', '#iamount', function() {
    var x = $(this).val();
    $(this).val(x.toString().replace(/,/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ","));
});
 </script>

<?php
// Close the database connection
mysqli_close($connection);
?>