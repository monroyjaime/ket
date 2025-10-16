<?php

session_start();
//require_once("app/php/db.php");
require_once("php/dbcat.php");
//$roles = ["Web master","Administrador","Cliente"];
$db = new DB();

if (!empty($_SERVER["HTTP_CLIENT_IP"]))
{
 $ip = $_SERVER["HTTP_CLIENT_IP"];
}
elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
{
 $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
}
else
{
 $ip = $_SERVER["REMOTE_ADDR"];
}

$consult = $db->consultas("SELECT role_name FROM roles ORDER BY num");
foreach ($consult as $value)
  $roles[] = $value->role_name;

  $sesionAlreadyActive = false;

  $numUsr = 0;
  $sesion_num = 0;
  $sesion_id = 0;
  $role = -1;

  if ( isset($_POST['usr_num']) && isset($_POST['ses_num']) && isset($_POST['ses_id']) ) 
  {

    $numUsr = intval($_POST['usr_num']);
    $sesion_num = intval($_POST['ses_num']);
    $sesion_id = intval($_POST['ses_id']);

    $_SESSION["usr_num"] = $numUsr;
    $_SESSION["ses_num"] = $sesion_num;
    $_SESSION["ses_id"] = $sesion_id;



    $consult = $db->consultas("SELECT short_name, full_name, client, email, rol FROM usuario WHERE num=".$numUsr);
    foreach ($consult as $value)
    {
      $shortName= $value->short_name;
      $fullName= $value->full_name;
      $client =  intval($value->client);
      $role = intval($value->rol);
      $usrMail = $value->email;
    }
    $_SESSION["role"] = $role;
    $_SESSION["usr_short_name"] = $shortName;
    $_SESSION["usr_full_name"] = $fullName;

    $sesionAlreadyActive = true;

  }
  else
  {
    $sesionAlreadyActive = (isset($_SESSION['ses_num']))? true : false;

/*    $consult = $db->consultas("SELECT COUNT(num) FROM sesion WHERE active='t' AND ip_client = '".$ip."'");
    foreach ($consult as $value)
      $sesionAlreadyActive = (intval($value->count) > 0)? true : false; */

    if($sesionAlreadyActive)
    {
      $numUsr = $_SESSION["usr_num"];
      $sesion_num = $_SESSION["ses_num"];
      $sesion_id = $_SESSION["ses_id"];
      $role = $_SESSION["role"];

      /*$consult = $db->consultas("SELECT usuario, num, id FROM sesion WHERE active='t' AND ip_client = '".$ip."'");
      foreach ($consult as $value)
      {
        $numUsr = intval($value->usuario);
        $sesion_num = intval($value->num);
        $sesion_id = intval($value->id);

      }*/
      $consult = $db->consultas("SELECT short_name, full_name, client, email, rol FROM usuario WHERE num=".$numUsr);
      foreach ($consult as $value)
      {
        $shortName= $value->short_name;
        $fullName= $value->full_name;
        $client =  intval($value->client);
        $role = intval($value->rol);
        $usrMail = $value->email;
      }  
    }
  }


    if($sesionAlreadyActive) 
    {
      $form  = '<div class="col-md-12">';
      $form .=   '<div class="bottom text-center">';
      $form .=   '<b>'.$roles[$role].'</b>';
      $form .=  '</div>';
      $form .=        '<div class="bottom text-center">';
      $form .=          '<i class="bi bi-person-circle icon-dark-blue icon-large"></i>';
      $form .=        '</div>';
      $form .=  '<form class="form" role="form" method="post" action="php/validateLogout.php" accept-charset="UTF-8" id="logout-nav">';
      $form .=      '<div class="form-group">';
      $form .=       '<label class="sr-only" for="input_email">Nombre:</label>';
      $form .=        '<input type="text" class="form-control" placeholder="'.$fullName.'" disabled readonly>';
      $form .=      '</div>';
      $form .=      '<div class="form-group">';
      $form .=        '<label class="sr-only" for="input_password">email:</label>';
      $form .=        '<input type="text" class="form-control" placeholder="'.$usrMail.'" disabled readonly>';
      $form .=     '</div>';
      $form .=      '<div class="form-group">';
      $form .=        '<input type="hidden" readonly="" name="sesion" value="'.$sesion_num.'">';
      $form .=     '</div>';
      $form .=      '<div class="form-group">';
      $form .=        '<button type="submit" class="btn btn-primary btn-block">Cerrar Sesion</button>';
      $form .=     '</div>';
      $form .=  '</form>';
      $form .= '</div>';

      $shortNameUsr = $shortName; //$_SESSION["usr_short_name"];
    }
    else
    {
      $form  = '<div class="col-md-12">';
      $form .=   '<div class="bottom text-center">';
      $form .=   '<b>Ingresar</b>';
      $form .=  '</div>';
      $form .=  '<form class="form" role="form" method="post" action="php/validateLogin.php" accept-charset="UTF-8" id="login-nav">';
      $form .=      '<div class="form-group">';
      $form .=       '<label class="sr-only" for="input_email">Email address</label>';
      $form .=        '<input type="email" class="form-control" id="input_email" name="input_email" placeholder="Email address" required>';
      $form .=      '</div>';
      $form .=      '<div class="form-group">';
      $form .=        '<label class="sr-only" for="input_password">Password</label>';
      $form .=        '<input type="password" class="form-control" id="input_password" name="input_password" placeholder="Password" required>';
      $form .=     '</div>';
      $form .=      '<div class="form-group">';
      $form .=        '<button type="submit" class="btn btn-primary btn-block">Enviar</button>';
      $form .=     '</div>';
      $form .=  '</form>';
      $form .= '</div>';

      $shortNameUsr = "";
    }

?>

<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="initial-scale=1, maximum-scale=1">
		<title>Ket Home</title>
        <link rel="Shortcut Icon" href="../favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">		
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">   
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

        <script type="text/javascript">

          var sesionNum = <?php echo $sesion_num;?>;
          var sesionId = <?php echo $sesion_id;?>;
          var usrNum = <?php echo $numUsr;?>;
          var roleNum = <?php echo $role;?>;

          function getListasPrec(rol){
            urlString = "listas/index.php?role_num="+rol;
              window.location.href = urlString;
          }

          function getListasPrecLinea(rol,linea)
          {
            switch(linea){
              case 1:
                urlString = "listas/indexL1.php?role_num="+rol;
              break
              case 2:
                urlString = "listas/indexL2.php?role_num="+rol;
              break
            }
              window.location.href = urlString;
          }

          function getCatalogoNormal(idDpto,role){   //,checkNum){   
              urlString =  "catalogo/indexDptoAll2.php?dpto_id="+idDpto+"&role_num="+role;
              window.location.href = urlString;
          }
          
          function getCatalogoUnique(idDpto,role){
              urlString ="catalogo/indexDptoAllUniPhotoList.php?dpto_id="+idDpto+"&role_num="+role;
              window.location.href = urlString;
          }
        </script>
        
        <style>
          
          body {
            background-image: url("img/fondoOscuroMobile.jpg");
            background-size: contain;
          }
          .navbar-nav .nav-link {
            color: #000;
          }
          .dropend .dropdown-toggle {
            color: #003272;
            margin-left: 1em;
          }
          .dropdown-item:hover {
            background-color: #003272;
            color: #fff;
          }
          .dropdown .dropdown-menu {
            display: none;
          }
          .dropdown:hover > .dropdown-menu,
          .dropend:hover > .dropdown-menu {
            display: block;
            margin-top: 0.125em;
            margin-left: 0.125em;
          }

          @media screen and (min-width: 769px) {
            body {
              background-image: url("img/fondoOscuro1.jpg");
              background-size: contain;

              background-position-x: center;
              
            }
          }

          

          @media screen and (min-width: 769px) {

            .dropend:hover > .dropdown-menu {
              position: absolute;
              top: 0;
              left: 100%;
            }
            .dropend .dropdown-toggle {
              margin-left: 0.5em;
            }
            
          }

          .icon-large {
            font-size: 25px;
          }
          .icon-dark-blue{
            color: #003272;
          }

          #imgLinks {
            padding-top: 25px;
            padding-bottom:25px; 
            padding-left: 100px;
            padding-right:100px;

          }

          @media screen and (min-width: 769px) {
            #imgLinks {
                padding-top: 25px;
                padding-bottom:25px; 
                padding-left: 1px;
                padding-right:1px;              
            }
          }

          #contactinfo {
            background-image: url("img/fondoClaroMobile.jpg");
            background-size: cover;            
          }

          @media screen and (min-width: 769px) {
            #contactinfo {
              background-image: url("img/fondoClaro1.jpg");
              background-size: cover;              
            }
          }

          #contact{
            padding-top: 25px;
            padding-left: 25px;

            font-family: Arial, sans-serif;
            color: #003272;
            font-weight: 800;
          }

          

          #login-dp{
              min-width: 250px;
              padding: 14px 14px 0;
              overflow:hidden;
              background-color:rgba(255,255,255,.8);
          }
          #login-dp .help-block{
              font-size:12px    
          }
          #login-dp .bottom{
              background-color:rgba(255,255,255,.8);
              border-top:1px solid #ddd;
              clear:both;
              padding:14px;
          }
          #login-dp .social-buttons{
              margin:12px 0    
          }
          #login-dp .social-buttons a{
              width: 49%;
          }
          #login-dp .form-group {
              margin-bottom: 10px;
          }
          .btn-fb{
              color: #fff;
              background-color:#3b5998;
          }
          .btn-fb:hover{
              color: #fff;
              background-color:#496ebc 
          }
          .btn-tw{
              color: #fff;
              background-color:#55acee;
          }
          .btn-tw:hover{
              color: #fff;
              background-color:#59b5fa;
          }
          /* @media(max-width:768px){
              #login-dp{
                  background-color: inherit;
                  color: #fff;
              }
              #login-dp .bottom{
                  background-color: inherit;
                  border-top:0 none;
              }
          } */


        </style>
	</head>	
	<body >
 

  <nav class="navbar navbar-expand-sm navbar-light fixed-top" style="background-color: #99b9d7;">
  <div class="container-fluid">
     <!-- Brand -->
     <a class="navbar-brand me-2 mb-1 d-flex align-items-center" href="#">
          <img
            src="catalogo/images/logo.png"
            height="40"
            alt="KET Logo"
            loading="lazy"
            style="margin-top: 2px;"
          />
        </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">              

<!--       <ul class="nav navbar-nav navbar-right"> 
        <li class="dropdown">   -->
      <li class="nav-item dropend"> 
<!--        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><b>Login</b> <span class="caret"></span></a> -->
          <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="bi bi-person-circle icon-dark-blue icon-large"></i><?php echo $shortNameUsr; ?></a>
          <ul id="login-dp" class="dropdown-menu">
            <li>
              <div class="row">

              <?php echo $form; ?>

              </div>
            </li>
          </ul>
        </li>  
      </ul> 






      <img src="catalogo/images/sublogo.png" 
        height="40"
        alt="KET sub Logo"
        loading="lazy"
        style="margin-top: 2px;">
    </div>
  </div>
</nav>




<!-- main content -->


<div class="buttom text-center">


<div id="demo" class="carousel slide" data-bs-ride="carousel">
 
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="d-flex justify-content-center" style="padding-top:100px; padding-bottom:25px;">
          <img class="d-block w-50" src="img/fondo1LandScape.jpg" alt="Promo2" style="max-height:50vh;max-width:50vh;">
      </div>    
    </div>
    <div class="carousel-item">
      <div class="d-flex justify-content-center" style="padding-top:100px;padding-bottom:25px">
        <img class="d-block w-50" src="img/fondo2LandScape.jpg" alt="Promo2" style="max-height:50vh;max-width:50vh;">
      </div>
    </div>
    <div class="carousel-item">
      <div class="d-flex justify-content-center " style="padding-top:100px; padding-bottom:25px;">
          <img class="d-block w-50" src="img/fondo3LandScape.jpg" alt="Promo2" style="max-height:50vh;max-width:50vh;">
      </div>
    </div>
  </div>
  <a class="carousel-control-prev" href="#demo" role="button" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
  </a>
  <a class="carousel-control-next" href="#demo" role="button" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
  </a>

  <ol class="carousel-indicators">
    <button type="button" data-bs-target="#demo" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="2"></button>
  </ol>
</div>

<div class="col text-center">
  <div class="row row-cols-1 row-cols-sm-3 g-3 ">
    <div id="imgLinks" class="d-flex justify-content-center" >
      <a  href="listas/index.php"><img class="d-block w-100" src="img/linea0.jpg" alt="linea0" style="max-height:50vh;max-width:50vh;"></a>
    </div>
    <div id="imgLinks" class="d-flex justify-content-center" >
      <a  href="listas/indexL1.php"><img class="d-block w-100" src="img/linea1.jpg" alt="linea1" style="max-height:50vh;max-width:50vh;"></a>    
    </div>
    <div id="imgLinks" class="d-flex justify-content-center" >
      <a  href="listas/indexL2.php"><img class="d-block w-100" src="img/linea2.jpg" alt="linea2" style="max-height:50vh;max-width:50vh;"></a>
    </div>
  </div>        
</div>

</div>

<div id="contactinfo" class="container-fluid">
  <div class="row">
    <div id="contact" class="col-sm">
      <h1>CONTACTANOS</h1>
      <p>
        <h3>Teléfonos:</h3>
        <h2>04143161207</h2>
      </p>
      <p>
        <h3>Redes:</h3>
        <h2>@ketccs</h2>
      </p>
      <p>
        <h3>e-mail:</h3>
        <h2>ventasket@gmail.com</h2>
      </p>


    </div>
    <div class="col-sm">
      2 of 2
    </div>
  </div>  

</div>



<script type="text/javascript" src="https://smartarget.online/loader.js?u=8b36b710663e9c3c9e0d1c99e724e6d2065cca9b"></script>

 

  </body>
  </html>