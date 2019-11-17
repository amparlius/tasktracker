<?php
	include_once("config.php");
	$ErrorMsgAdd="";
	$ErrorMsg= "";
	$admin_edited="<div><i>отредактировано администратором</i></div>";
	
	session_start();
	$user="guest"; 
	
	// user login
	if (  ($_POST["user"]==="admin") && (MD5($_POST["password"])==="202cb962ac59075b964b07152d234b70") ){
		$_SESSION["username"] = "admin";
		$user="admin";
	}
	else {
		if (isset($_POST["user"])) {$ErrorMsg= "Ошибка!<br>Проверьте правильность ввода логина / пароля. ";};
	};
	  
	if ($_SESSION["username"]==="admin" ) {
		  $user="admin";
	};
	$enable_table_edit = ($user=="admin") ? " class='table_data'" : "";
	
	// user logout 
	if ($_GET["logout"]==="true") {
		unset($_SESSION["username"]);
		session_destroy();
		$_SESSION["username"]="guest";
		$message = "Вы вышли из системы";
	};	
	
	// sorting
	
		// page 
		if (isset($_GET["page"])) {
			$page=$_GET["page"]; 
			$_SESSION["page"]=$page;
		}
		else {
			if (isset($_SESSION["page"])) {
				$page=$_SESSION["page"]; 
			}
			else{
				$page=1;
			}
		}
		
		//order
		if (isset($_GET["order"])) {
			$order=$_GET["order"]; 
			$_SESSION["order"]=$order;
		}
		else {
			$order = (isset($_SESSION["order"])) ? $_SESSION["order"] : "ASC"; 
		}
		if ($order=="ASC") {
			$order_next =  "DESC";
			$sort_icon="&nbsp;<span class='glyphicon glyphicon-sort-by-alphabet'>&#9660;</span>";
		}
		else {
			$order_next =  "ASC";
			$sort_icon="&nbsp;<span class='glyphicon glyphicon-sort-by-alphabet-alt'>&#9650;</span>";
		};
		
		// colomn
		$colomn="task_id";
		$alowed_pages=array ("task_id" => true, "username" => true, "email" => true, "task_text" => true, "status" => true);
		if (isset($_GET["colomn"])) {
			if (isset($alowed_pages[$_GET["colomn"]]) ) {
				$colomn=$_GET["colomn"]; 
			}
		}
		else {
			if (isset($_SESSION["colomn"])) {
				$colomn=$_SESSION["colomn"]; 
			}
		};
		$_SESSION["colomn"]=$colomn;
	

	
	// add / edit database 
	if (isset($_POST["email"])) {
		if ((($user=="admin")&&($_POST["id"]>0)) || ($_POST["id"]=="")){
			$frm_task_id=($_POST["id"]);
			$frm_username=($_POST["username"]);
			$frm_email=($_POST["email"]);
			$frm_task_text=($_POST["task_text"]);
			$frm_status=($_POST["status"]);
			if ($frm_status=="on"){$frm_status=1;}else{$frm_status=0;};

			$sth = $db->prepare("REPLACE INTO beejee_test (task_id, username, email, task_text, status) values (:frm_task_id, :frm_username, :frm_email, :frm_task_text, :frm_status)");
			$sth->bindParam(':frm_task_id', $frm_task_id);
			$sth->bindParam(':frm_username', $frm_username);
			$sth->bindParam(':frm_email', $frm_email);
			$sth->bindParam(':frm_task_text', $frm_task_text);
			$sth->bindParam(':frm_status', $frm_status);
			  
			try {  
				$sth->execute();
				$message = ($_POST["id"]>0) ? "Задача успешно изменена" : "Задача успешно добавлена";
			}  
			catch(PDOException $e) {  
				echo "Ошибка при добавлении / обновлении строки в базу: ".  $e->getMessage();  
			};
		}
		else { 
			$ErrorMsgAdd="Необходимо войти в систему";
		}	  
	};
			  	
	// pagination calculations			
	$limit=3;
	$sth = $db->prepare("SELECT * FROM beejee_test");
	$sth->execute();
	$allResp = $sth->fetchAll(PDO::FETCH_ASSOC);
	$total_results = $sth->rowCount();
	$total_pages = ceil($total_results/$limit);
	$start = ($page-1)*$limit;
	
	// getting data for main table 		
	$sql="SELECT * FROM beejee_test ORDER BY " .$colomn. " ". $order ." LIMIT " . $start . ", " . $limit . ";";
	$sth = $db->prepare($sql);
	$sth->execute();
	$sth->setFetchMode(PDO::FETCH_OBJ);
	$results = $sth->fetchAll();	
	$tbody="";
 
	foreach($results as $result){
		$tbody=$tbody. "<tr " . $enable_table_edit . ">\n";
		$tbody=$tbody. "	<td>" . $result->task_id . "</td>\n";
		$tbody=$tbody. "	<td>" . htmlspecialchars($result->username) . "</td>\n";
		$tbody=$tbody. "	<td>" . htmlspecialchars($result->email) . "</td>\n";
		$tbody=$tbody. "	<td>" . htmlspecialchars($result->task_text);  
		if ($result->status==1) {$tbody=$tbody. $admin_edited;};
		$tbody=$tbody. "</td>\n";
		$tbody=$tbody. "	<td>" . str_replace("1","&#10004;",str_replace("0","",$result->status)) . "</td>\n";   
		$tbody=$tbody. "</tr>\n";   
	};		

	// creating dynamic navigation buttons and message area
	$menu_buttons="";
	if ($message!=""){$menu_buttons=$menu_buttons . "<span class='btn btn-success'>" . $message . "</span> &nbsp;";} 
			  if ($user==="admin"){
					$menu_buttons=$menu_buttons . 'Пользователь: admin &nbsp; <a class="btn btn-primary" href="index.php?logout=true">Выйти</a> &nbsp;';
			  }
			  else {
				  $menu_buttons=$menu_buttons . ' <button class="btn btn-primary" data-toggle="modal" data-target="#loginModal">Войти</button> &nbsp;';  
			  };
				
?>
<!doctype html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="Shortcut Icon" href="/favicon.ico" type="image/x-icon"> 	
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<title>Задачи</title>
	</head>
	<body>
		<!-- Header -->
		 <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom shadow-sm">
			  <h1 class="my-0 mr-md-auto font-weight-normal">Задачи</h1>
			  <? echo $menu_buttons;?>
			  <button class="btn btn-primary" id="addTaskButton"> Добавить задачу </button> &nbsp;
			  <button class="btn btn-primary" data-toggle="modal" data-target="#helpModal" id="helpModalButton"> Справка </button>
		</div>
	
		<!-- Modal add / edit task -->
		<div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
		  <div class="modal-dialog" role="document">
				<form class="needs-validation" name="addTaskModalForm" id="addTaskModalForm" method="POST" action="index.php">
					<div class="modal-content">
						<div class="modal-header">
							<h2 class="modal-title" id="atmf_title" >Добавление задачи</h2>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
								<div class="form-row">
									<div class="col-sm">
										<input type="hidden" id="atmf_id" name="id" class="form-control" >
										<input type="text"   id="atmf_username" name="username" class="form-control"  placeholder="Имя пользователя" required>
										<input type="email"  id="atmf_email" name="email"    class="form-control"  placeholder="E-mail" required>
										Задача выполнена&nbsp;<input type="checkbox" id="atmf_status" name="status">
									</div>
								</div>
								<div class="form-row">
									<div class="col-sm">
										<label for="exampleFormControlTextarea1">Текст задачи</label>
										<textarea class="form-control"  id="atmf_task_text" name="task_text" rows="8" placeholder="Текст задачи"  required></textarea>
									</div>
								</div>
						</div> 
						<div class="modal-footer">
							<button type="submit" class="btn btn-primary">Отправить</button>
						</div>
					</div>
				</form>	
			</div>
		</div>


		<!-- Modal login -->
		<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		  <div class="modal-dialog" role="document">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Вход в систему</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span>
				</button>
			  </div>
			  <div class="modal-body">
				<form class="needs-validation" method="POST">
					<input type="text" name="user" class="form-control" id="username" aria-describedby="emailHelp" placeholder="Введите имя пользователя" required value="<? echo $_POST["user"];?>">
					<input type="password" name="password" class="form-control" id="password" placeholder="Введите пароль" required value="<? echo $_POST["password"];?>">
			  </div>
			  <div class="modal-footer">
			   <?php if (($ErrorMsg!="")||($ErrorMsgAdd!="")) {echo "<span class='alert alert-danger'>". $ErrorMsg . $ErrorMsgAdd . "</span> &nbsp; ";};?>
				<button type="submit" class="btn btn-primary">ОК</button>
			  </div></form>
			</div>
		  </div>
		</div>

		<!-- Modal help -->
		<div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-hidden="true">
		  <div class="modal-dialog" role="document">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Справка</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span>
				</button>
			  </div>
			  <div class="modal-body">
				<ul>
					<li> Для редактирования записей необходимо войти в систему под учетной записью администратора и сделать даблклик на интересующем столбце таблицы.</li> 
					<li> Добавление записей можно осуществлять без входа в систему.</li>
				</ul>	
			  </div>
			  <div class="modal-footer">
				<button class="btn btn-primary" data-dismiss="modal">ОК</button>
			  </div></form>
			</div>
		  </div>
		</div>

		<!-- Main page -->
		<div class="container">
			<div class="row">
				<div class="col">					
					<main role="main" class="inner cover">
						<div class="row">
							<div class="col">
								<table class="table table-hover table-bordered">
									<thead class="table-dark">
										<tr style="cursor:hand !important;">
											<th scope="col" onclick="sort('task_id');">#<?if ($colomn=="task_id"){echo $sort_icon;} ?></th>
											<th scope="col" onclick="sort('username');">Имя пользователя<?if ($colomn=="username"){echo $sort_icon;};?> </th>
											<th scope="col" onclick="sort('email');">E-mail<?if ($colomn=="email"){echo $sort_icon;}?></th>
											<th scope="col" onclick="sort('task_text');">Текст задачи<?if ($colomn=="task_text"){echo $sort_icon;}?></th>
											<th scope="col" onclick="sort('status');">Статус<?if ($colomn=="status"){echo $sort_icon;}?></th>
										</tr>
									</thead>
									<tbody>
										<?php echo $tbody;?> 
									</tbody>
								</table>
							
								<div class="clearfix"></div>
								
								<nav class="row justify-content-center">
								  <ul class="pagination">
									<?php for($p=1; $p<=$total_pages; $p++){?>
										<li class="page-item <?= $page == $p ? 'active' : ''; ?>"><a class="page-link" href="<?= '?page='.$p; ?>"><?= $p; ?></a></li>
									<?php }?>
								  </ul>
								</nav>
							</div>	
						</div>
					</main>
				</div>	
			</div>	
		</div>

  
	
		
		<!-- Optional JavaScript -->
		<!-- jQuery first, then Popper.js, then Bootstrap JS --> 

		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
		
		
		 <script>
			// filling addTaskModal for editing entry 
			$('.table_data').dblclick(function(){
				$('#atmf_id').val( $(this).children('td')[0].innerHTML);
				$('#atmf_username').val( $(this).children('td')[1].innerHTML);
				$('#atmf_email').val( $(this).children('td')[2].innerHTML);
				$('#atmf_task_text').val( $(this).children('td')[3].innerText.replace("<?echo $admin_edited?>", ""));
				if ($(this).children('td')[4].innerText=='✔') {
					$('#atmf_status').prop('checked', true) 
				} 
				else {
					$('#atmf_status').prop('checked', false) 
				};
				$('#atmf_title').text("Редактирование задачи");
				$("#addTaskModal").modal(focus);
			});
			
			// filling addTaskModal for adding entry 
			$('#addTaskButton').click(function(){
				$('#atmf_id').val('');
				$('#atmf_username').val('<?echo $user?>');
				$('#atmf_email').val('');
				$('#atmf_task_text').val('');
				$('#atmf_status').val(0);
				$('#atmf_title').text("Редактирование задачи");
				$("#addTaskModal").modal(focus);
			});
			
			// sorting 
			function sort(colomn){
				document.location='index.php?page=<?php echo $page;?>&colomn=' + colomn + '&order=<?php echo $order_next;?>';
			}
		</script> 
		<?php 
		// loading of login modal if there were errors with login or editing rows without authorisation 
		if (($ErrorMsg!="") || ($ErrorMsgAdd!=""))    {echo '<script> $("#loginModal").modal(focus);</script>';}; 
		?>
	</body>
</html>